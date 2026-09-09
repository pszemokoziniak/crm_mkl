<?php

namespace App\Http\Controllers;

use App\Models\A1;
use App\Models\Badania;
use App\Models\Bhp;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\Pbioz;
use App\Models\Uprawnienia;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ReportsController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index');
    }

    public function koniecUprawinien(\Illuminate\Http\Request $request)
    {
        $today = Carbon::today();
        $todayStr = $today->toDateString();

        // Okno "kończące się": przeterminowane do 7 dni wstecz + kończące się
        // w ciągu N dni. "all" = wszystkie ważne.
        //
        // Domyślnie 90 dni: przy 30 kierownicy nie widzieli na wejściu żadnych
        // uprawnień (te chodzą w cyklach rocznych) i raport wyglądał, jakby
        // dotyczył wyłącznie A1.
        $daysInput = $request->input('days', 90);
        $all = $daysInput === 'all';
        $days = $all ? null : max(1, (int) $daysInput);

        $graceStart = $today->copy()->subDays(7)->toDateString();
        $windowEnd = $all ? $today->copy()->addYears(50)->toDateString()
                          : $today->copy()->addDays($days)->toDateString();

        // Kierownik dostał ten raport, ale tylko dla ludzi ze swoich budów —
        // ten sam zakres, co licznik "wygasające terminy" na jego pulpicie.
        $user = Auth::user();
        $moiPracownicy = null;

        if ($user && $user->isKierownik()) {
            $moiPracownicy = ContactWorkDate::query()
                ->whereIn('organization_id', Organization::managedBy($user->contactId())->pluck('id'))
                ->where(function ($q) use ($todayStr) {
                    $q->whereNull('end')->orWhere('end', '>=', $todayStr);
                })
                ->pluck('contact_id')->unique()->values();
        }

        // Zawężenie do "moich" ludzi; dla biura przepuszcza zapytanie bez zmian.
        $moje = function ($query) use ($moiPracownicy) {
            if ($moiPracownicy !== null) {
                $query->whereIn('contacts.id', $moiPracownicy);
            }

            return $query;
        };

        // Wspólne: pobyt tylko istniejących (nieusuniętych) pracowników.
        $rows = collect();

        $push = function ($items, $category) use (&$rows) {
            foreach ($items as $it) {
                $rows->push([
                    'client_id' => $it->id,
                    'last_name' => $it->last_name,
                    'first_name' => $it->first_name,
                    'name' => $it->name,
                    'category' => $category,
                    'start' => $it->start,
                    'end' => $it->end,
                ]);
            }
        };

        $push($moje(Bhp::join('contacts', 'bhps.contact_id', '=', 'contacts.id'))
            ->join('bhp_typs', 'bhps.bhpTyp_id', '=', 'bhp_typs.id')
            ->whereNull('contacts.deleted_at')
            ->whereBetween('bhps.end', [$graceStart, $windowEnd])
            ->get(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'bhp_typs.name', 'bhps.start', 'bhps.end']), 'BHP');

        $push($moje(A1::join('contacts', 'a1_s.contact_id', '=', 'contacts.id'))
            ->whereNull('contacts.deleted_at')
            ->whereBetween('a1_s.end', [$graceStart, $windowEnd])
            ->selectRaw("contacts.id, contacts.first_name, contacts.last_name, 'A1' as name, a1_s.start, a1_s.end")
            ->get(), 'A1');

        $push($moje(Badania::join('contacts', 'badanias.contact_id', '=', 'contacts.id'))
            ->join('badania_typs', 'badanias.badaniaTyp_id', '=', 'badania_typs.id')
            ->whereNull('contacts.deleted_at')
            ->whereBetween('badanias.end', [$graceStart, $windowEnd])
            ->get(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'badania_typs.name', 'badanias.start', 'badanias.end']), 'Badania lekarskie');

        $push($moje(Uprawnienia::join('contacts', 'uprawnienias.contact_id', '=', 'contacts.id'))
            ->join('uprawnienia_typs', 'uprawnienias.uprawnieniaTyp_id', '=', 'uprawnienia_typs.id')
            ->whereNull('contacts.deleted_at')
            ->whereBetween('uprawnienias.end', [$graceStart, $windowEnd])
            ->get(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'uprawnienia_typs.name', 'uprawnienias.start', 'uprawnienias.end']), 'Uprawnienia');

        $push($moje(Pbioz::join('contacts', 'pbiozs.contact_id', '=', 'contacts.id'))
            ->whereNull('contacts.deleted_at')
            ->whereBetween('pbiozs.end', [$graceStart, $windowEnd])
            ->get(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'pbiozs.name', 'pbiozs.start', 'pbiozs.end']), 'PBIOZ');

        // Filtr po nazwisku/nazwie
        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $rows = $rows->filter(fn ($r) =>
                Str::contains(mb_strtolower($r['last_name'].' '.$r['first_name']), $search)
                || Str::contains(mb_strtolower((string) $r['name']), $search)
            );
        }

        // Zawsze sort po dacie końca (najbliższe/przeterminowane na górze).
        $data = $rows->sortBy(fn ($r) => $r['end'])->values();

        // Brak dokumentów: pracownicy z aktualnym/przyszłym pobytem, którzy nie
        // mają WAŻNEGO (end >= dziś) dokumentu w danej kategorii.
        $assignedIds = $moiPracownicy !== null ? $moiPracownicy : ContactWorkDate::query()
            ->where(function ($q) use ($todayStr) {
                $q->whereNull('end')->orWhere('end', '>=', $todayStr);
            })
            ->pluck('contact_id')->unique();

        $validIn = fn ($model, $fk) => $model::where('end', '>=', $todayStr)->pluck($fk)->unique()->flip();
        $vBad = Badania::where('end', '>=', $todayStr)->pluck('contact_id')->unique()->flip();
        $vA1 = A1::where('end', '>=', $todayStr)->pluck('contact_id')->unique()->flip();
        $vUpr = Uprawnienia::where('end', '>=', $todayStr)->pluck('contact_id')->unique()->flip();
        $vBhp = Bhp::where('end', '>=', $todayStr)->pluck('contact_id')->unique()->flip();

        $braki = Contact::whereIn('id', $assignedIds)
            ->orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(function ($c) use ($vBad, $vA1, $vUpr, $vBhp) {
                $missing = [];
                if (! isset($vBad[$c->id])) $missing[] = 'Badania';
                if (! isset($vA1[$c->id])) $missing[] = 'A1';
                if (! isset($vUpr[$c->id])) $missing[] = 'Uprawnienia';
                if (! isset($vBhp[$c->id])) $missing[] = 'BHP';

                return $missing ? [
                    'id' => $c->id,
                    'name' => trim($c->last_name.' '.$c->first_name),
                    'missing' => $missing,
                ] : null;
            })
            ->filter()->values();

        return Inertia::render('Reports/TerminUprawnien', [
            'filters' => ['search' => $request->input('search'), 'days' => (string) $daysInput],
            'data' => $data,
            'braki' => $braki,
        ]);
    }
}