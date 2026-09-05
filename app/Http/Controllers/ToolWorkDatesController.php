<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindPracownicyRequest;
use App\Http\Requests\StoreBudowaPracownicyRequest;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Narzedzia;
use App\Models\Organization;
use App\Models\ToolWorkDate;
use App\Services\MagazynSprzetu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ToolWorkDatesController extends Controller
{
    public function organizationWorkers($id) {
        $workers = DB::table('contact_work_dates', 'cwd')
            ->select('contacts.first_name', 'contacts.last_name', 'cwd.organization_id', 'cwd.start', 'cwd.end', 'funkcjas.name', 'funkcjas.id')
            ->join('contacts', 'cwd.contact_id', '=', 'contacts.id')
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->where('cwd.organization_id', $id)
            ->whereNull('cwd.deleted_at')
            ->orderBy('last_name')
            ->get();

        return $workers;
    }

    public function index(Organization $organization)
    {
        $tools = ToolWorkDate::with('narzedzia')
            ->where('organization_id', $organization->id)
            ->filter(\Illuminate\Support\Facades\Request::only('search', 'trashed'))
            ->get();

        $groupedTools = $tools->groupBy(function ($item) {
            return $item->narzedzia ? $item->narzedzia->name : 'Nieznane';
        })->map(function ($group, $name) {
            return [
                'name' => $name,
                'total_qty' => $group->sum('narzedzia_nb'),
                'items' => $group->map(function ($item) {
                    // waznosc_badan bywa surowym Carbon (pełne ISO) albo zepsutą
                    // datą (np. rok 0001) — formatujemy i odsiewamy nierealne.
                    $badania = optional($item->narzedzia)->waznosc_badan;
                    $badaniaData = ($badania && $badania->year >= 2000 && $badania->year <= 2100)
                        ? $badania->format('Y-m-d')
                        : null;

                    return [
                        'id' => $item->id,
                        'narzedzia_id' => $item->narzedzia_id,
                        'narzedzia_nb' => $item->narzedzia_nb,
                        'numer_seryjny' => optional($item->narzedzia)->numer_seryjny ?: '-',
                        'waznosc_badan' => $badaniaData,
                        // Termin pobytu sprzętu na budowie — od kiedy tu stoi.
                        'od' => $item->start ? (string) $item->start : null,
                        'do' => $item->end ? (string) $item->end : null,
                    ];
                }),
            ];
        })->values();

        return Inertia::render('NarzedziaBudowa/Index', [
            'organization' => $organization,
            'filters' => \Illuminate\Support\Facades\Request::all('search', 'trashed'),
            'groupedTools' => $groupedTools,
        ]);
    }
    public function create(Organization $organization, MagazynSprzetu $magazyn)
    {
        $dzis = Carbon::today()->toDateString();

        // Wolne sztuki, czyli takie bez trwającego przypisania. Liczymy je
        // z przypisań, nie z licznika w kolumnie — licznik nie wie o tym,
        // że pobyt sprzętu na budowie mógł się już skończyć.
        $wolne = Narzedzia::with($magazyn->relacje())
            ->orderBy('numer_seryjny')
            ->get()
            ->filter(fn (Narzedzia $n) => ! $magazyn->trwajacePrzypisanie($n, $dzis));

        return Inertia::render('NarzedziaBudowa/Create', [
            'organization' => $organization,
            'grupy' => $magazyn->grupy($wolne, $dzis),
            'naBudowie' => $this->sprzetBudowy($organization, $magazyn, $dzis),
        ]);
    }

    /**
     * Sprzęt stojący na tej budowie — z numerem seryjnym, badaniami i terminem.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sprzetBudowy(Organization $organization, MagazynSprzetu $magazyn, string $dzis): array
    {
        return ToolWorkDate::with(['narzedzia.typ', 'narzedzia.files'])
            ->where('organization_id', $organization->id)
            ->filter(\Illuminate\Support\Facades\Request::only('search', 'trashed'))
            ->get()
            ->map(function (ToolWorkDate $wpis) use ($magazyn, $dzis) {
                $narzedzie = $wpis->narzedzia;

                return [
                    'id' => $wpis->id,
                    'narzedzia_id' => $wpis->narzedzia_id,
                    'nazwa' => $narzedzie ? $narzedzie->name : null,
                    'numer_seryjny' => $narzedzie ? ($narzedzie->numer_seryjny ?: null) : null,
                    'waznosc_badan' => $narzedzie ? $magazyn->dataBadan($narzedzie) : null,
                    'badania_status' => $narzedzie ? $magazyn->statusBadan($narzedzie, $dzis) : null,
                    'od' => $wpis->start ? (string) $wpis->start : null,
                    'do' => $wpis->end ? (string) $wpis->end : null,
                    'zakonczony' => $wpis->end !== null && (string) $wpis->end < $dzis,
                ];
            })
            ->values()
            ->all();
    }

    public function store(Request $request, Organization $organization, MagazynSprzetu $magazyn)
    {
        $dane = $request->validate([
            'narzedzia_ids' => ['required', 'array', 'min:1'],
            'narzedzia_ids.*' => ['integer', 'exists:narzedzias,id'],
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ], [
            'narzedzia_ids.required' => 'Zaznacz co najmniej jedną sztukę.',
            'start.required' => 'Podaj datę od.',
            'end.after_or_equal' => 'Data do nie może być wcześniejsza niż data od.',
        ]);

        $dzis = Carbon::today()->toDateString();
        $zajete = [];
        $wydane = 0;

        foreach (Narzedzia::whereIn('id', $dane['narzedzia_ids'])->with('toolWorkDates')->get() as $narzedzie) {
            // Ktoś mógł wydać tę sztukę, zanim ten formularz został wysłany.
            if ($magazyn->trwajacePrzypisanie($narzedzie, $dzis)) {
                $zajete[] = trim($narzedzie->name.' '.($narzedzie->numer_seryjny ?: ''));
                continue;
            }

            ToolWorkDate::create([
                'narzedzia_id' => $narzedzie->id,
                'organization_id' => $organization->id,
                'narzedzia_nb' => 1,
                'start' => $dane['start'],
                'end' => $dane['end'] ?? null,
            ]);

            $narzedzie->ilosc_budowa = ($narzedzie->ilosc_budowa ?? 0) + 1;
            $narzedzie->save();

            $wydane++;
        }

        $redirect = Redirect::route('budowy.narzedzia', $organization->id);

        if ($wydane === 0) {
            return $redirect->with('error', 'Nic nie wydano — zaznaczony sprzęt jest już na budowie.');
        }

        $komunikat = 'Wydano na budowę: '.$wydane.' '.($wydane === 1 ? 'sztukę' : 'szt.').'.';

        if ($zajete) {
            return $redirect->with('error', $komunikat.' Pominięto (już na budowie): '.implode(', ', $zajete));
        }

        return $redirect->with('success', $komunikat);
    }

    public function edit(Organization $organization, ToolWorkDate $narzedzia)
    {
        return Inertia::render('NarzedziaBudowa/Edit', [
            'organization' => $organization,
            'toolWorkDate' => [
                'id' => $narzedzia->id,
                'narzedzia_nb' => $narzedzia->narzedzia_nb,
                'narzedzia' => $narzedzia->narzedzia,
            ],
            'narzedzie' => $narzedzia->narzedzia,
        ]);
    }

    public function update(Request $request, Organization $organization, ToolWorkDate $narzedzia)
    {
        $request->validate([
            'narzedzia_nb' => ['required', 'numeric', 'min:1'],
        ]);

        $nowaIlosc = (int) $request->narzedzia_nb;
        $staraIlosc = (int) $narzedzia->narzedzia_nb;
        $roznica = $nowaIlosc - $staraIlosc;

        $narzedzie = Narzedzia::find($narzedzia->narzedzia_id);

        $magazyn = $narzedzie->ilosc_magazyn ?? $narzedzie->ilosc_all ?? 0;
        if ($roznica > 0 && $magazyn < $roznica) {
            return Redirect::back()->with('error', 'Brak wystarczającej ilości w magazynie.');
        }

        $narzedzie->ilosc_magazyn = $magazyn - $roznica;
        $narzedzie->ilosc_budowa = ($narzedzie->ilosc_budowa ?? 0) + $roznica;
        $narzedzie->save();

        $narzedzia->narzedzia_nb = $nowaIlosc;
        $narzedzia->save();

        return Redirect::route('budowy.narzedzia', $organization->id)->with('success', 'Ilość zaktualizowana.');
    }

    public function destroy(Organization $organization, ToolWorkDate $toolWorkDate)
    {
        $data = Narzedzia::find($toolWorkDate->narzedzia_id);
        if ($data) {
            $data->ilosc_magazyn = (integer) ($data->ilosc_magazyn ?? $data->ilosc_all ?? 0) + (integer) $toolWorkDate->narzedzia_nb;
            $data->ilosc_budowa = (integer) ($data->ilosc_budowa ?? 0) - (integer) $toolWorkDate->narzedzia_nb;
            $data->save();
        }

        $toolWorkDate->delete();

        return Redirect::route('budowy.narzedzia', $organization->id)->with('success', 'Usunięto.');
    }
}
