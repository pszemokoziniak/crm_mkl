<?php

namespace App\Http\Controllers;

use App\Models\Badania;
use App\Models\Bhp;
use App\Models\Contact;
use App\Models\ZmianaKadrowa;
use App\Models\ContactWorkDate;
use App\Models\Narzedzia;
use App\Models\Organization;
use App\Models\Pbioz;
use App\Models\Uprawnienia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Kierownik nie ma dashboardu — jego jedynym widokiem jest lista budów.
        if ($user->owner === 3) {
            return redirect('/budowy');
        }

        $contact = Contact::where('user_id', $user->id)
            ->orWhere(function($query) use ($user) {
                $query->where('first_name', $user->first_name)
                      ->where('last_name', $user->last_name);
            })
            ->first();

        $contact_id = $contact ? $contact->id : null;

        $now = now()->format('Y-m-d');
        $in30Days = now()->addDays(30)->format('Y-m-d');

        $expiringItems = collect();
        $myOrgIds = collect();

        if ($user->owner === 3) {
            // "Budowa kierownika" = kierownictwo obecne lub byłe (patrz Organization::scopeManagedBy).
            $myOrgIds = Organization::managedBy($contact_id)->pluck('id');
        }

        if ($user->owner === 1 || $user->owner === 2 || $user->owner === 3) {
            // Uprawnienia
            $uprawnieniaQuery = Uprawnienia::with(['uprawnieniaTyp'])
                ->join('contacts', 'uprawnienias.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('uprawnienias.*');

            // Badania
            $badaniaQuery = Badania::with(['badaniaTyp'])
                ->join('contacts', 'badanias.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('badanias.*');

            // BHP
            $bhpQuery = Bhp::with(['bhpTyp'])
                ->join('contacts', 'bhps.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('bhps.*');

            // PBIOZ
            $pbiozQuery = Pbioz::join('contacts', 'pbiozs.contact_id', '=', 'contacts.id')
                ->whereNull('contacts.deleted_at')
                ->whereBetween('end', [$now, $in30Days])
                ->select('pbiozs.*');

            // Filtrowanie pracowników, którzy są OBECNIE na budowie
            $filterByActiveWorkers = function($query) use ($myOrgIds, $user, $now) {
                $query->whereIn('contacts.id', function($q) use ($myOrgIds, $user, $now) {
                    $q->select('contact_id')
                      ->from('contact_work_dates')
                      ->whereNull('deleted_at')
                      ->whereDate('start', '<=', $now)
                      ->where(function ($q2) use ($now) {
                          $q2->whereNull('end')->orWhereDate('end', '>=', $now);
                      });

                    if ($user->owner === 3) {
                        $q->whereIn('organization_id', $myOrgIds);
                    }
                });
            };

            $filterByActiveWorkers($uprawnieniaQuery);
            $filterByActiveWorkers($badaniaQuery);
            $filterByActiveWorkers($bhpQuery);
            $filterByActiveWorkers($pbiozQuery);

            $uprawnienia = $uprawnieniaQuery->get()->map(fn($item) => $this->mapExpiringItem($item, 'Uprawnienia', $item->uprawnieniaTyp->name ?? 'Brak typu', $now));
            $badania = $badaniaQuery->get()->map(fn($item) => $this->mapExpiringItem($item, 'Badania lekarskie', $item->badaniaTyp->name ?? 'Brak typu', $now));
            $bhp = $bhpQuery->get()->map(fn($item) => $this->mapExpiringItem($item, 'Szkolenie BHP', $item->bhpTyp->name ?? 'Brak typu', $now));
            $pbioz = $pbiozQuery->get()->map(fn($item) => $this->mapExpiringItem($item, 'PBIOZ', 'PBIOZ', $now));

            $expiringItems = $uprawnienia->concat($badania)->concat($bhp)->concat($pbioz)->sortBy('end')->values();
        }

        $organizations_user = collect();
        $organizations_biuro = collect();

        if ($user->owner === 3) {
            $organizations_user = Organization::with(['inzynier', 'krajTyp'])
                ->addSelect([
                    'inzynierowie_names' => ContactWorkDate::query()
                        ->join('contacts', 'contacts.id', '=', 'contact_work_dates.contact_id')
                        ->selectRaw(
                            "GROUP_CONCAT(DISTINCT CONCAT(contacts.last_name, ' ', contacts.first_name)
                         ORDER BY contacts.last_name SEPARATOR ', ')"
                        )
                        ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                        ->where('contacts.funkcja_id', 6)
                        ->activeOn($now),
                ])
                ->whereIn('id', $myOrgIds)
                ->filter(Request::only('search', 'trashed', 'my'))
                ->whereNull('organizations.deleted_at')
                ->orderBy('organizations.created_at', 'desc')
                ->get()
                ->transform(fn($org) => $this->transformOrganization($org, $now));
        } else {
            $organizations_biuro = Organization::with(['inzynier', 'krajTyp'])
                ->addSelect([
                    'inzynierowie_names' => ContactWorkDate::query()
                        ->join('contacts', 'contacts.id', '=', 'contact_work_dates.contact_id')
                        ->selectRaw(
                            "GROUP_CONCAT(DISTINCT CONCAT(contacts.last_name, ' ', contacts.first_name)
                         ORDER BY contacts.last_name SEPARATOR ', ')"
                        )
                        ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                        ->where('contacts.funkcja_id', 6)
                        ->activeOn($now),
                ])
                ->whereHas('contactWorkDates', function ($query) use ($now) {
                    $query->activeOn($now);
                })
                ->filter(Request::only('search', 'trashed', 'my'))
                ->whereNull('organizations.deleted_at')
                ->orderBy('organizations.created_at', 'desc')
                ->paginate(100)
                ->getCollection()
                ->transform(fn($org) => $this->transformOrganization($org, $now));
        }

        // Budowy do archiwizacji: mają pobyty istniejących pracowników, ale
        // żaden nie jest już niezakończony (wszyscy zjechali).
        // Warsztat nigdy się nie kończy, więc nie ma go po co archiwizować.
        $doArchiwizacji = Organization::query()
            ->tylkoBudowy()
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('contact_work_dates as cwd')
                    ->join('contacts as c', 'c.id', '=', 'cwd.contact_id')
                    ->whereColumn('cwd.organization_id', 'organizations.id')
                    ->whereNull('cwd.deleted_at')
                    ->whereNull('c.deleted_at');
            })
            ->whereNotExists(function ($q) use ($now) {
                $q->select(DB::raw(1))->from('contact_work_dates as cwd')
                    ->join('contacts as c', 'c.id', '=', 'cwd.contact_id')
                    ->whereColumn('cwd.organization_id', 'organizations.id')
                    ->whereNull('cwd.deleted_at')
                    ->whereNull('c.deleted_at')
                    ->where(function ($w) use ($now) {
                        $w->whereNull('cwd.end')->orWhere('cwd.end', '>=', $now);
                    });
            })
            ->orderBy('numerBud', 'desc')
            ->get(['id', 'nazwaBud', 'numerBud'])
            ->map(fn ($o) => ['id' => $o->id, 'nazwaBud' => $o->nazwaBud, 'numerBud' => $o->numerBud])
            ->values();

        // Pracownicy z aktualnym/przyszłym pobytem, ale bez A1 ważnego dziś.
        $bezWaznegoA1 = Contact::query()
            ->whereIn('id', function ($q) use ($now) {
                $q->select('contact_id')->from('contact_work_dates')
                    ->whereNull('deleted_at')
                    ->where(function ($w) use ($now) {
                        $w->whereNull('end')->orWhere('end', '>=', $now);
                    });
            })
            ->whereDoesntHave('a1', fn ($q) => $q->where('end', '>=', $now))
            ->orderBy('last_name')->orderBy('first_name')
            ->limit(100)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($c) => ['id' => $c->id, 'first_name' => $c->first_name, 'last_name' => $c->last_name])
            ->values();

        $stats = [
            'pracownicy' => Contact::count(),
            'budowy' => Organization::tylkoBudowy()->count(),
            'sprzet' => Narzedzia::count(),
            'wygasajace' => $expiringItems->count(),
        ];

        // Zmiany pobytów czekające na kadry — dział HR to uprawnienia biuro.
        $zmianyKadrowe = collect();

        if (in_array($user->owner, [1, 2], true)) {
            $zmianyKadrowe = ZmianaKadrowa::with(['contact', 'budowaZ', 'budowaDo'])
                ->nieobsluzone()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn (ZmianaKadrowa $z) => [
                    'id' => $z->id,
                    'pracownik' => $z->contact
                        ? trim($z->contact->last_name.' '.$z->contact->first_name)
                        : 'pracownik usunięty',
                    'typ_label' => $z->typLabel(),
                    'budowa_z' => optional($z->budowaZ)->nazwaBud,
                    'budowa_do' => optional($z->budowaDo)->nazwaBud,
                    'nowy_termin' => $z->new_start
                        ? $z->new_start->format('Y-m-d').' → '.optional($z->new_end)->format('Y-m-d')
                        : null,
                    'kiedy' => $z->created_at?->format('d.m.Y H:i'),
                ]);
        }

        return Inertia::render('Dashboard/Index', [
            'filters' => Request::all('search', 'trashed', 'my'),
            'zmiany_kadrowe' => $zmianyKadrowe,
            'zmiany_kadrowe_licznik' => in_array($user->owner, [1, 2], true)
                ? ZmianaKadrowa::nieobsluzone()->count()
                : 0,
            'stats' => $stats,
            'do_archiwizacji' => $doArchiwizacji,
            'bez_a1' => $bezWaznegoA1,
            'expiring_items' => $expiringItems,
            'organizations_user' => $organizations_user,
            'organizations_biuro' => $organizations_biuro,
            'user_owner' => [$user->id, $user->owner, $contact_id],
        ]);
    }

    private function transformOrganization($organization, $now)
    {
        return [
            'id' => $organization->id,
            'nazwaBud' => $organization->nazwaBud,
            'numerBud' => $organization->numerBud,
            'kierownikBud_id' => $organization->kierownikBud_id,
            'inzynier_id' => $organization->inzynier_id,
            'city' => $organization->city,
            'country' => $organization->krajTyp ? $organization->krajTyp : null,
            'workers_count' => ContactWorkDate::where('organization_id', $organization->id)
                ->activeOn($now)
                ->count(),
            'inzynier_name' => $organization->inzynierowie_names,
            'inzynier' => $organization->inzynier ? [
                'id' => $organization->inzynier->id,
                'first_name' => $organization->inzynier->first_name,
                'last_name' => $organization->inzynier->last_name,
            ] : null,
            'deleted_at' => $organization->deleted_at,
        ];
    }

    private function mapExpiringItem($item, $category, $type, $now)
    {
        $contact = Contact::find($item->contact_id);
        $currentWork = ContactWorkDate::with('organization')
            ->where('contact_id', $item->contact_id)
            ->activeOn($now)
            ->first();

        return [
            'id' => $item->id,
            'end' => $item->end,
            'category' => $category,
            'type' => $type,
            'contact' => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
            ],
            'organization' => $currentWork && $currentWork->organization ? [
                'id' => $currentWork->organization->id,
                'nazwaBud' => $currentWork->organization->nazwaBud,
            ] : null,
        ];
    }
}
