<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Models\Contact;
use App\Models\Funkcja;
use App\Models\ContactWorkDate;
use App\Models\JezykTyp;
use App\Models\KrajTyp;
use App\Models\Organization;
use App\Models\Prognoza;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class OrganizationsController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        // Kontakt zalogowanego (kierownik) — do oznaczenia jego aktywnej budowy 🟢
        $myContactId = Auth::user()?->contactId();

        $hasExplicitSort = request()->filled('sort');
        $sort = request('sort', 'numerBud');
        $direction = request('direction') === 'asc' ? 'asc' : 'desc';

        // Mapowanie "publicznych" nazw sortów -> realne kolumny/aliasy SQL
        $allowedSorts = [
            'name'                 => 'organizations.name',
            'nazwaBud'             => 'organizations.nazwaBud',
            'numerBud'             => 'organizations.numerBud',
            'city'                 => 'organizations.city',
            'active_workers_count' => 'active_workers_count',
            'country'              => 'kt.name',
            'created_at'           => 'organizations.created_at',
        ];

        if (!array_key_exists($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        $query = Organization::query()
            ->with('krajTyp')
            ->addSelect([
                'active_workers_count' => ContactWorkDate::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                    ->activeOn($today),

                // Pobyty jeszcze niezakończone (łącznie z przyszłymi) — zero oznacza,
                // że budowa jest gotowa do archiwizacji.
                'unfinished_workers_count' => ContactWorkDate::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                    ->whereHas('contact')
                    ->notFinished($today),

                'all_workers_count' => ContactWorkDate::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                    ->whereHas('contact'),

                'kierownicy_names' => ContactWorkDate::query()
                    ->join('contacts', 'contacts.id', '=', 'contact_work_dates.contact_id')
                    ->selectRaw(
                        "GROUP_CONCAT(DISTINCT CONCAT(contacts.last_name, ' ', contacts.first_name)
                     ORDER BY contacts.last_name SEPARATOR ', ')"
                    )
                    ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                    ->where('contacts.funkcja_id', 1)
                    ->activeOn($today),

                'inzynierowie_names' => ContactWorkDate::query()
                    ->join('contacts', 'contacts.id', '=', 'contact_work_dates.contact_id')
                    ->selectRaw(
                        "GROUP_CONCAT(DISTINCT CONCAT(contacts.last_name, ' ', contacts.first_name)
                     ORDER BY contacts.last_name SEPARATOR ', ')"
                    )
                    ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                    ->where('contacts.funkcja_id', 6)
                    ->activeOn($today),

                // Czy zalogowany kierownik ma na tej budowie aktywne kierownictwo (dziś)
                'is_active_for_me' => ContactWorkDate::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                    ->where('contact_work_dates.contact_id', $myContactId)
                    ->activeOn($today),
            ]);

        // Kierownik widzi tylko swoje budowy (aktywne kierownictwo); admin/biuro — wszystkie.
        $query->visibleTo(Auth::user());

        // Filtrowanie po wyszukiwarce i statusie usunięcia (soft delete)
        $query->filter(Request::only('search', 'trashed'));

        if ($sort === 'country') {
            $query->leftJoin('kraj_typs as kt', 'kt.id', '=', 'organizations.country_id')
                ->addSelect('organizations.*')
                ->orderBy('kt.name', $direction);
        }

        // Domyślnie (bez ręcznego sortowania) aktywna budowa użytkownika ląduje na górze.
        if (!$hasExplicitSort) {
            $query->orderByDesc('is_active_for_me');
        }

        if ($sort === 'numerBud') {
            // Numer projektu bywa różnej długości — sortujemy liczbowo, nie tekstowo.
            $query->orderByRaw('CAST(organizations.numerBud AS UNSIGNED) '.($direction === 'asc' ? 'asc' : 'desc'));
        } else {
            $query->orderBy($allowedSorts[$sort], $direction);
        }

        // Fallback sort
        if ($sort !== 'created_at') {
            $query->orderBy('organizations.created_at', 'desc');
        }

        return Inertia::render('Organizations/Index', [
            'filters' => Request::all('search', 'trashed', 'sort', 'direction'),
            'organizations' => $query
                ->with('kierownikProjektu')
                ->paginate(20)
                ->withQueryString()
                ->through(fn ($organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'nazwaBud' => $organization->nazwaBud,
                    'numerBud' => $organization->numerBud,
                    'country' => $organization->krajTyp ? $organization->krajTyp : null,
                    'kierownicy' => $organization->kierownicy_names ?: null,
                    'inzynierowie' => $organization->inzynierowie_names ?: null,
                    'warsztat' => (bool) $organization->warsztat,
                    'kierownik_projektu' => $organization->kierownikProjektu
                        ? trim($organization->kierownikProjektu->last_name.' '.$organization->kierownikProjektu->first_name)
                        : null,
                    'active_workers_count' => (int) ($organization->active_workers_count ?? 0),
                    'is_active' => (bool) ($organization->is_active_for_me ?? false),
                    // Budowa, na której wszyscy zakończyli pobyt — kandydat do archiwum.
                    'ready_to_archive' => (int) ($organization->all_workers_count ?? 0) > 0
                        && (int) ($organization->unfinished_workers_count ?? 0) === 0,
                    'deleted_at' => $organization->deleted_at,
                ]),
        ]);
    }

    /**
     * Pracownicy ze stanowiskiem "Kierownik Projektu" — opcje do wyboru
     * opiekuna kontraktu. Zwolnionych nie proponujemy, ale osobę już
     * przypisaną do tej budowy zostawiamy na liście, żeby edycja
     * formularza jej po cichu nie skasowała.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function kierownicyProjektow(?int $wybranyId = null): array
    {
        $funkcjaId = Funkcja::kierownikProjektuId();

        return Contact::query()
            ->where(function ($query) use ($funkcjaId, $wybranyId) {
                $query->where('funkcja_id', $funkcjaId)
                    ->where('status_zatrudnienia', '!=', Contact::STATUS_ZWOLNIONY);

                if ($wybranyId) {
                    $query->orWhere('id', $wybranyId);
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Contact $osoba) => [
                'id' => $osoba->id,
                'name' => trim($osoba->last_name.' '.$osoba->first_name),
            ])
            ->all();
    }

    /**
     * Proxy wyszukiwarki klientów do API CRM (crm_mklv2). Token trzymamy po
     * stronie serwera — przeglądarka go nie widzi. Gdy integracja nie jest
     * skonfigurowana albo CRM nie odpowiada, zwracamy pustą listę, a picker
     * degraduje się do zwykłego wpisywania nazwy.
     */
    public function searchClients()
    {
        $q = trim((string) Request::query('q', ''));
        $base = rtrim((string) config('services.crm.url'), '/');
        $token = (string) config('services.crm.token');

        if ($base === '' || $token === '') {
            return response()->json([]);
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->get($base.'/api/clients', ['q' => $q]);

            if (!$response->successful()) {
                return response()->json([]);
            }

            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json([]);
        }
    }

    public function create()
    {
        return Inertia::render('Organizations/Create', [
            'krajTyps' => KrajTyp::orderByName()->get(),
            'kierownicyProjektow' => $this->kierownicyProjektow(),
            'kierownikBud' => Contact::where('funkcja_id', 1)->orderBy('last_name')->get(['id','first_name','last_name']),
            'inzyniers' => Contact::where('funkcja_id', 6)->orderBy('last_name')->get(['id','first_name','last_name']),
        ]);
    }

    public function store(StoreOrganizationRequest $req)
    {
        $today = Carbon::today()->toDateString();

        $org = new Organization();
        $org->name = $req->name;
        $org->account_id = 0;
        $org->nazwaBud = $req->nazwaBud;
        $org->numerBud = $req->numerBud;
        $org->city = $req->city;
        $org->zaklad = $req->zaklad;
        $org->kierownik_projektu_id = $req->kierownik_projektu_id;
        $org->warsztat = $req->boolean('warsztat');
        $org->country_id = $req->country_id;
        $org->addressBud = $req->addressBud;
        $org->addressKwat = $req->addressKwat;
        $org->crm_client_id = $req->input('crm_client_id');
        $org->save();

        $kierownicyIds = array_values(array_filter((array) $req->input('kierownikBud_ids', [])));
        $inzynierIds   = array_values(array_filter((array) $req->input('inzynier_ids', [])));

        $rows = [];
        foreach (array_merge($kierownicyIds, $inzynierIds) as $contactId) {
            $rows[] = [
                'organization_id' => (int) $org->id,
                'contact_id'      => (int) $contactId,
                'start'           => $today,
                'end'             => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        if ($rows !== []) {
            foreach ($rows as $row) {
                ContactWorkDate::query()->updateOrCreate(
                    [
                        'organization_id' => $row['organization_id'],
                        'contact_id'      => $row['contact_id'],
                        'start'           => $row['start'],
                    ],
                    [
                        'end'        => $row['end'],
                        'updated_at' => $row['updated_at'],
                    ]
                );
            }
        }

        return Redirect::route('organizations')->with('success', 'Budowa stworzona.');
    }

    public function edit(Organization $organization)
    {
        // Dostęp do budowy autoryzuje middleware biuro-kierownik-permission
        // (OrganizationPolicy@view => Organization::scopeManagedBy). Tu tylko tryb read-only dla kierownika.
        $flag = Auth::user()->owner === 3;

        // Podsumowanie kadrowe: szczyt zapotrzebowania (max tygodniowa prognoza)
        // vs liczba pracowników przypisanych do budowy (distinct osoby z pobytów).
        $peak = Prognoza::with('prognozadates')
            ->where('organization_id', $organization->id)
            ->orderByDesc('workers_count')
            ->first();

        $summary = [
            'peak' => $peak ? (int) $peak->workers_count : null,
            'peakStart' => optional(optional($peak)->prognozadates)->start,
            'peakEnd' => optional(optional($peak)->prognozadates)->end,
            'assigned' => ContactWorkDate::where('organization_id', $organization->id)
                ->distinct()->count('contact_id'),
        ];

        return Inertia::render('Organizations/Edit', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'crm_client_id' => $organization->crm_client_id,
                'nazwaBud' => $organization->nazwaBud,
                'numerBud' => $organization->numerBud,
                'city' => $organization->city,
                'kierownikBud_id' => $organization->kierownikBud_id,
                'inzynier_id' => $organization->inzynier_id,
                'zaklad' => $organization->zaklad,
                'kierownik_projektu_id' => $organization->kierownik_projektu_id,
                'warsztat' => (bool) $organization->warsztat,
                'country_id' => $organization->country_id,
                'addressBud' => $organization->addressBud,
                'addressKwat' => $organization->addressKwat,
                'deleted_at' => $organization->deleted_at,
//                'contacts' => $organization->contacts()->funkcja()->orderByName()->get()->map->only('id', 'last_name', 'position', 'phone', 'name'),
            ],
            // Kto jeszcze pracuje — decyduje o tym, czy budowę da się zarchiwizować.
            'unfinishedWorkers' => $organization->unfinishedWorkDates()
                ->with('contact')
                ->orderBy('end')
                ->get()
                ->map(fn (ContactWorkDate $workDate) => [
                    'name' => $workDate->contact
                        ? trim($workDate->contact->last_name.' '.$workDate->contact->first_name)
                        : 'pracownik',
                    'end' => $workDate->end,
                ])
                ->values(),
            'krajTyps' => KrajTyp::orderByName()->get(),
            'kierownicyProjektow' => $this->kierownicyProjektow($organization->kierownik_projektu_id),
            'kierownikBud' => Contact::with('user')
                ->with('funkcja')
                ->where('funkcja_id', 1)
                ->get(),
            'inzyniers' => Contact::with('user')
                ->with('funkcja')
                ->where('funkcja_id', 6)
                ->get(),
            'contactsFree' => Contact::where('organization_id', null)->where('funkcja_id', '!=', 1)->get()->map->only('id','first_name','last_name'),
            'contacts' => Contact::with('funkcja')
                ->where('organization_id', $organization->id)
                ->orderByName()
                ->paginate(1000)
                ->withQueryString()
                ->through(fn ($contact) => [
                    'id' => $contact->id,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'phone' => $contact->phone,
                    'funkcja_id' => $contact->funkcja_id,
                    'deleted_at' => $contact->deleted_at,
                    'funkcja' => $contact->funkcja,
                ]),
            'user_owner' => Auth::user()->owner,
            'summary' => $summary,
            'flag' => $flag,
        ]);
    }

    public function update(Organization $organization)
    {
        // Mutacje budowy są tylko dla admina/biura — kierownika blokuje middleware biuro-permission (read-only).
        $organization->update(
            Request::validate([
                'name' => ['required', 'max:100'],
                'crm_client_id' => ['nullable', 'integer'],
                'nazwaBud' => ['nullable', 'max:2250'],
                'numerBud' => ['nullable', 'max:550'],
                'city' => ['nullable', 'max:150'],
                'kierownikBud_id' => ['nullable', 'max:25'],
                'inzynier_id' => ['nullable', 'max:25'],
                'zaklad' => ['nullable', 'max:2000'],
                'kierownik_projektu_id' => ['nullable', 'integer', 'exists:contacts,id'],
                'warsztat' => ['boolean'],
                'country_id' => ['nullable', 'max:25'],
                'addressBud' => ['nullable', 'max:2000'],
                'addressKwat' => ['nullable', 'max:2500'],
            ])
        );

        return Redirect::back()->with('success', 'Budowa poprawiona.');
    }

    /**
     * Archiwizacja budowy (soft delete).
     * Blokują tylko pobyty, które jeszcze trwają lub dopiero się zaczną — zakończone
     * zostają jako historia i nie stoją na drodze. Admin może archiwizować mimo blokady.
     */
    public function destroy(Organization $organization)
    {
        $blocking = $organization->unfinishedWorkDates()
            ->with('contact')
            ->orderBy('end')
            ->get();

        $force = Request::boolean('force') && Auth::user()->isAdmin();

        if ($blocking->isNotEmpty() && ! $force) {
            return Redirect::back()->with('error', $this->blockingWorkersMessage($blocking));
        }

        $organization->delete();

        return Redirect::back()->with('success', 'Budowa zarchiwizowana.');
    }

    /**
     * Komunikat mówiący wprost, kto blokuje archiwizację i do kiedy —
     * wcześniej było ogólne "usuń pracowników" i nie było wiadomo których.
     */
    private function blockingWorkersMessage($blocking): string
    {
        $names = $blocking->take(3)->map(function (ContactWorkDate $workDate) {
            $contact = $workDate->contact;
            $name = $contact ? trim($contact->last_name.' '.$contact->first_name) : 'pracownik';

            return $workDate->end
                ? $name.' (do '.Carbon::parse($workDate->end)->format('d.m.Y').')'
                : $name.' (bez daty końca)';
        })->implode(', ');

        $pozostali = $blocking->count() - min(3, $blocking->count());

        return 'Budowa nie została zarchiwizowana — pracownicy jeszcze na niej pracują: '
            .$names
            .($pozostali > 0 ? ' i '.$pozostali.' innych' : '')
            .'. Popraw daty pobytu albo poczekaj do ich zakończenia.';
    }

    public function restore(Organization $organization)
    {
        $organization->restore();

        return Redirect::back()->with('success', 'Budowa przywrócona.');
    }
}
