<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindPracownicyRequest;
use App\Http\Requests\StoreBudowaPracownicyRequest;
use App\Models\A1;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\BuildingTimeSheet;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Services\KolizjaPobytu;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class BudowaPracownicyController extends Controller
{
    /**
     * Stanowiska, które mogą być na kilku budowach naraz — nie blokuje ich
     * sprawdzanie kolizji przy przypisywaniu pracowników.
     * Uwaga: to inna lista niż kierownictwo budowy (znacznik przy funkcji).
     */
    private const MULTI_SITE_FUNKCJA_IDS = [
        Funkcja::KIEROWNIK,
        Funkcja::INZYNIER,
    ];

    public function organizationWorkers($id) {
        $workers = DB::table('contact_work_dates', 'cwd')
            ->select('cwd.id', 'cwd.contact_id', 'contacts.first_name', 'contacts.last_name', 'contacts.status_zatrudnienia', 'cwd.organization_id', 'cwd.start', 'cwd.end', 'funkcjas.name')
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

        return Inertia::render('Pracownicy/Index', [
            // Nagłówek podstrony potrzebuje nazwy budowy, nie samego id.
            'organization' => ['id' => $organization->id, 'nazwaBud' => $organization->nazwaBud],
            'organization_id' => $organization->id,
            'filters' => Request::all('search', 'trashed'),
            'contactworkdates' => ContactWorkDate::with('organization')
                ->with(['contact' => function ($query) {
                    $query->withTrashed();
                }])
                ->with('contact.funkcja')
                // Nieobecności z dzisiaj — pracownik może być na budowie i na L4 naraz.
                ->with(['contact.holidays' => fn ($query) => $query->with('shiftStatus')
                    ->coveringDate(Carbon::today()->toDateString())])
                ->join('contacts', 'contact_work_dates.contact_id', '=', 'contacts.id')
                ->where('contact_work_dates.organization_id', $organization->id)
                ->orderBy('contacts.last_name')
                ->select('contact_work_dates.*') // Select only columns from contact_work_dates table
                ->filter(Request::only('search', 'trashed'))
                ->paginate(100)
                ->withQueryString()
                ->through(fn ($contactworkdate) => [
                    'id' => $contactworkdate->id,
                    'contact' => $contactworkdate->contact,
                    'funkcja' => $contactworkdate->funkcja,
                    'start' => $contactworkdate->start,
                    'end' => $contactworkdate->end,
                    // Czy pobyt na TEJ budowie jeszcze trwa — inna rzecz niż
                    // status zatrudnienia pracownika w firmie.
                    'on_site' => $contactworkdate->end === null
                        || Carbon::parse($contactworkdate->end)->toDateString() >= Carbon::today()->toDateString(),
                    // Powód nieobecności, jeśli akurat dziś go nie ma na budowie.
                    'nieobecnosc' => optional(optional($contactworkdate->contact)->holidays->first())->label,
                ]),
            'user_owner' => Auth::user()->owner,
        ]);
    }
    public function create(Organization $organization) {

        $workers = $this->organizationWorkers($organization->id);

        return Inertia::render('Pracownicy/Create', [
            'contacts' => $workers,
            'organization' => $organization,
        ]);
    }
    public function store(StoreBudowaPracownicyRequest $request, Organization $organization)
    {
        $start = $request->start;
        $end   = $request->end;

        $toAssign = [];
        foreach (($request->checkedValues ?? []) as $id) {
            $toAssign[] = (int) $id;
        }

        $toAssign = array_values(array_unique(array_filter($toAssign)));

        if (empty($toAssign)) {
            return Redirect::back()->with('error', 'Nie wybrano żadnego pracownika.');
        }

        // Walidacja dla zwykłych pracowników (nie mogą być nigdzie zajęci)
        $busyGlobalIds = DB::table('contact_work_dates')
            ->whereNull('deleted_at')
            ->whereIn('contact_id', $toAssign)
            ->where('start', '<=', $end)
            ->where('end', '>=', $start)
            ->distinct()
            ->pluck('contact_id')
            ->all();

        if (!empty($busyGlobalIds)) {
            $busyNames = Contact::query()
                ->whereIn('id', $busyGlobalIds)
                ->orderBy('last_name')
                ->get(['first_name', 'last_name'])
                ->map(fn ($c) => $c->last_name . ' ' . $c->first_name)
                ->implode(', ');

            return Redirect::back()->with(
                'error',
                'Niedostępni w tym terminie (zajęci na innej budowie): ' . ($busyNames ?: 'wybrani pracownicy') . '.'
            );
        }

        DB::transaction(function () use ($toAssign, $organization, $start, $end) {
            foreach ($toAssign as $contactId) {
                $data = new ContactWorkDate();
                $data->contact_id = $contactId;
                $data->organization_id = $organization->id;
                $data->start = $start;
                $data->end = $end;
                $data->save();
            }
        });

        return Redirect::route('pracownicy.index', $organization->id)->with('success', 'Pracownicy dodani.');
    }

    public function edit(Organization $organization, ContactWorkDate $contactWorkDate)
    {
        return Inertia::render('Pracownicy/Edit', [
            'contactWorkDate' => [
                'id' => $contactWorkDate->id,
                'start' => $contactWorkDate->start,
                'end' => $contactWorkDate->end,
            ],
            'contact' => Contact::where('id', $contactWorkDate->contact_id)->first(),
            'organization' => Organization::where('id', $contactWorkDate->organization_id)->first(),
        ]);
    }

    public function update(ContactWorkDate $contactWorkDate)
    {
        $contactWorkDate->update(
            \Illuminate\Support\Facades\Request::validate([
                'start' => ['required', 'date'],
                'end' => ['required', 'date'],
            ])
        );
        return Redirect::route('pracownicy.index', $contactWorkDate->organization_id)->with('success', 'Poprawiono.');
    }

    /**
     * Zbiorcze skrócenie (albo wydłużenie) pobytu — jednym ruchem dla całej
     * ekipy przenoszonej na inną budowę. Zapis idzie przez model, więc rejestr
     * zmian kadrowych dostaje wpisy tak samo jak przy poprawianiu pojedynczo.
     */
    public function zbiorczaDataKonca(Organization $organization): RedirectResponse
    {
        $dane = Request::validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'end' => ['required', 'date'],
        ], [
            'ids.required' => 'Nie zaznaczono nikogo.',
            'end.required' => 'Podaj datę końca pobytu.',
        ]);

        $pobyty = ContactWorkDate::with('contact')
            ->where('organization_id', $organization->id)
            ->whereIn('id', $dane['ids'])
            ->get();

        if ($pobyty->isEmpty()) {
            return Redirect::back()->with('error', 'Nie znaleziono zaznaczonych pobytów na tej budowie.');
        }

        // Data końca przed początkiem pobytu to błąd, a nie skrócenie.
        $zaWczesnie = $pobyty->filter(fn (ContactWorkDate $p) => $p->start > $dane['end']);

        if ($zaWczesnie->isNotEmpty()) {
            $nazwiska = $zaWczesnie
                ->map(fn (ContactWorkDate $p) => optional($p->contact)->last_name.' (od '.$p->start.')')
                ->implode(', ');

            return Redirect::back()->with('error',
                'Data końca jest wcześniejsza niż początek pobytu: '.$nazwiska.'. Popraw datę albo odznacz te osoby.');
        }

        DB::transaction(function () use ($pobyty, $dane) {
            foreach ($pobyty as $pobyt) {
                $pobyt->update(['end' => $dane['end']]);
            }
        });

        return Redirect::back()->with('success',
            'Ustawiono koniec pobytu ('.$dane['end'].') dla '.$pobyty->count().' '
            .($pobyty->count() === 1 ? 'osoby' : 'osób').'.');
    }

    public function destroy(ContactWorkDate $contactWorkDate)
    {
        $organizationId = $contactWorkDate->organization_id;
        $contactId = $contactWorkDate->contact_id;

        $contactWorkDate->delete();

        BuildingTimeSheet::where('contact_id', $contactId)
            ->where('organization_id', $organizationId)
            ->delete();

        return Redirect::route('pracownicy.index', $organizationId)->with('success', 'Usunięto.');
    }

    public function find(FindPracownicyRequest $request, Organization $organization)
    {
        $start = $request->start;
        $end   = $request->end;

        $availableData = $this->getAvailableWorkersData($organization, $start, $end);
        $workers = $this->organizationWorkers($organization->id);

        return Inertia::render('Pracownicy/Create', [
            'specialists'  => $availableData['specialists'],
            'contactsFree' => $availableData['contactsFree'],
            'contacts'     => $workers,
            'organization' => $organization,
            'start'        => $start,
            'end'          => $end,
        ]);
    }

    private function getAvailableWorkersData(Organization $organization, $start, $end)
    {
        // 1) Globalnie zajęci (dla zwykłych pracowników)
        $busyGlobalIds = DB::table('contact_work_dates')
            ->whereNull('deleted_at')
            ->where('start', '<=', $end)
            ->where('end', '>=', $start)
            ->distinct()
            ->pluck('contact_id')
            ->all();

        // 2) Specjaliści (Kierownik/Inżynier) - mogą pracować na wielu budowach
        $specialists = Contact::query()
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->select([
                'contacts.id',
                'contacts.first_name',
                'contacts.last_name',
                'contacts.phone',
                'contacts.funkcja_id',
                'contacts.status_zatrudnienia',
                'funkcjas.name as fn_name',
            ])
            ->whereIn('contacts.funkcja_id', self::MULTI_SITE_FUNKCJA_IDS)
            ->where('contacts.status_zatrudnienia', '!=', Contact::STATUS_ZWOLNIONY)
            ->orderBy('contacts.last_name', 'asc')
            ->get();

        // 3) Pozostali - muszą być wolni globalnie
        $contactsFree = Contact::query()
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->select([
                'contacts.id',
                'contacts.first_name',
                'contacts.last_name',
                'contacts.phone',
                'contacts.funkcja_id',
                'contacts.status_zatrudnienia',
                'funkcjas.name as fn_name',
            ])
            ->whereNotIn('contacts.funkcja_id', self::MULTI_SITE_FUNKCJA_IDS)
            ->whereNotIn('contacts.id', $busyGlobalIds)
            ->where('contacts.status_zatrudnienia', '!=', Contact::STATUS_ZWOLNIONY)
            ->orderBy('contacts.last_name', 'asc')
            ->get();

        return [
            'specialists' => $specialists,
            'contactsFree' => $contactsFree,
        ];
    }

    public function management(Organization $organization)
    {
        // Które stanowiska liczą się jako kierownictwo — znacznik przy funkcji
        // w Ustawieniach, nie lista zaszyta w kodzie.
        $funkcjeKierownictwa = Funkcja::kierownictwoIds();

        $management = DB::table('contact_work_dates', 'cwd')
            ->select('cwd.id', 'cwd.contact_id', 'contacts.first_name', 'contacts.last_name', 'cwd.start', 'cwd.end', 'funkcjas.name')
            ->join('contacts', 'cwd.contact_id', '=', 'contacts.id')
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->where('cwd.organization_id', $organization->id)
            ->whereNull('cwd.deleted_at')
            ->whereIn('contacts.funkcja_id', $funkcjeKierownictwa)
            ->orderBy('last_name')
            ->get();

        $specialists = Contact::query()
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->select(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'funkcjas.name as fn_name'])
            ->whereIn('contacts.funkcja_id', $funkcjeKierownictwa)
            ->where('contacts.status_zatrudnienia', '!=', Contact::STATUS_ZWOLNIONY)
            ->orderBy('last_name')
            ->get();

        // Pobyty osób z listy — okienko potwierdzenia ostrzega, gdy termin
        // nakłada się na inną budowę.
        $pobyty = ContactWorkDate::with('organization')
            ->whereIn('contact_id', $specialists->pluck('id'))
            ->orderBy('start')
            ->get()
            ->groupBy('contact_id')
            ->map(fn ($grupa) => $grupa->map(fn (ContactWorkDate $w) => [
                'nazwaBud' => optional($w->organization)->nazwaBud,
                'start' => $w->start,
                'end' => $w->end,
            ])->values());

        return Inertia::render('Pracownicy/Kierownictwo', [
            'organization' => $organization,
            'management' => $management,
            'specialists' => $specialists,
            'pobyty' => $pobyty,
        ]);
    }

    public function storeManagement(Organization $organization, KolizjaPobytu $kolizja)
    {
        Request::validate([
            'contact_id' => ['required', 'exists:contacts,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $contact = Contact::with('funkcja')->find(Request::get('contact_id'));

        // Ta sama reguła co przy przypisywaniu z karty pracownika.
        $blad = $kolizja->komunikat($contact, (int) $organization->id, Request::get('start'), Request::get('end'));

        if ($blad) {
            return Redirect::back()->with('error', $blad);
        }

        ContactWorkDate::create([
            'organization_id' => $organization->id,
            'contact_id' => Request::get('contact_id'),
            'start' => Request::get('start'),
            'end' => Request::get('end'),
        ]);

        return Redirect::back()->with('success', 'Dodano do kierownictwa.');
    }

    public function a1Index(Organization $organization)
    {
        $organization->load('krajTyp');
        $orgCountryId = $organization->country_id;
        $today = Carbon::today()->toDateString();
        $search = Request::input('search');

        // Kazdy pobyt (contact_work_date) na tej budowie osobno - A1 weryfikuje
        // sie per pobyt, nie per nazwisko: pracownik zjezdzajacy wczesniej na
        // inna budowe potrzebuje nowego A1 na nowy termin.
        $stays = ContactWorkDate::query()
            ->where('contact_work_dates.organization_id', $organization->id)
            ->join('contacts', 'contacts.id', '=', 'contact_work_dates.contact_id')
            ->whereNull('contacts.deleted_at')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('contacts.first_name', 'like', '%'.$search.'%')
                        ->orWhere('contacts.last_name', 'like', '%'.$search.'%');
                });
            })
            ->orderByRaw('(contact_work_dates.end IS NULL OR contact_work_dates.end >= ?) DESC', [$today])
            ->orderByRaw('contact_work_dates.end IS NULL DESC')
            ->orderBy('contact_work_dates.end', 'desc')
            ->orderBy('contacts.last_name')
            ->get([
                'contact_work_dates.id',
                'contact_work_dates.contact_id',
                'contact_work_dates.start',
                'contact_work_dates.end',
                'contacts.first_name',
                'contacts.last_name',
            ]);

        $a1sByContact = A1::with('kraj')
            ->whereIn('contact_id', $stays->pluck('contact_id')->unique()->all())
            ->get()
            ->groupBy('contact_id');

        $rows = $stays->map(function ($stay) use ($a1sByContact, $orgCountryId, $today) {
            $a1s = $a1sByContact->get($stay->contact_id, collect());
            $coverage = $this->resolveA1Coverage($stay->start, $stay->end, $orgCountryId, $a1s);

            if ($stay->end !== null && $stay->end < $today) {
                $period = 'zakonczony';
            } elseif ($stay->start !== null && $stay->start > $today) {
                $period = 'przyszly';
            } else {
                $period = 'trwa';
            }

            return [
                'id' => $stay->id,
                'contact_id' => $stay->contact_id,
                'last_name' => $stay->last_name,
                'first_name' => $stay->first_name,
                'start' => $stay->start,
                'end' => $stay->end,
                'period' => $period,
                'status' => $coverage['status'],
                'a1' => $coverage['a1'],
            ];
        })->values();

        $summary = [
            'total' => $rows->count(),
            'ok' => $rows->where('status', 'ok')->count(),
            // Pokrywa termin, ale nie da sie potwierdzic kraju (dokument bez kraju).
            'do_weryfikacji' => $rows->where('status', 'brak_kraju')->count(),
            // Realny brak: zaden A1 nie pokrywa tego pobytu (albo zly kraj, albo brak dat).
            'braki' => $rows->whereIn('status', ['brak', 'wygasle', 'czesciowe', 'zly_kraj', 'brak_dat'])->count(),
        ];

        return Inertia::render('Building/A1', [
            'build' => $organization->id,
            'buildDetails' => $organization,
            'orgCountry' => $organization->krajTyp->name ?? null,
            'rows' => $rows,
            'summary' => $summary,
            'filters' => Request::all('search'),
        ]);
    }

    /**
     * Ustala, czy pobyt na budowie jest pokryty waznym A1.
     *
     * Kolejnosc od najlepszego do najgorszego przypadku; zwraca status i
     * dokument, ktory najlepiej pasuje do pokazania w tabeli.
     */
    private function resolveA1Coverage($stayStart, $stayEnd, $orgCountryId, $a1s): array
    {
        // Bez dat pobytu nie ma czego weryfikowac (czesc pobytow zapisano bez dat).
        if (!$stayStart || !$stayEnd) {
            return ['status' => 'brak_dat', 'a1' => null];
        }

        // Daty to stringi ISO 'Y-m-d' - porownanie leksykograficzne jest poprawne.
        $covers = fn ($a) => $a->start && $a->end && $a->start <= $stayStart && $a->end >= $stayEnd;
        $overlaps = fn ($a) => $a->start && $a->end && $a->start <= $stayEnd && $a->end >= $stayStart;

        $full = $a1s->filter($covers);

        // 1) Pelne pokrycie + kraj zgodny z krajem budowy.
        if ($orgCountryId !== null) {
            $ok = $full->first(fn ($a) => $a->kraj_typs_id !== null && (int) $a->kraj_typs_id === (int) $orgCountryId);
            if ($ok) {
                return $this->a1Row('ok', $ok);
            }
        }

        // 2) Pelne pokrycie, ale dokument bez wpisanego kraju - nie mozna potwierdzic.
        $noCountry = $full->first(fn ($a) => $a->kraj_typs_id === null);
        if ($noCountry) {
            return $this->a1Row('brak_kraju', $noCountry);
        }

        // 3) Pelne pokrycie, ale A1 na inny kraj.
        $wrongCountry = $full->sortByDesc('end')->first();
        if ($wrongCountry) {
            return $this->a1Row('zly_kraj', $wrongCountry);
        }

        // 4) Jest A1 zachodzace na termin, ale nie obejmuje calego pobytu.
        $partial = $a1s->filter($overlaps)->sortByDesc('end')->first();
        if ($partial) {
            return $this->a1Row('czesciowe', $partial);
        }

        // 5) Pracownik ma jakies A1, ale zadne nie dotyczy tego terminu.
        $any = $a1s->sortByDesc('end')->first();
        if ($any) {
            return $this->a1Row('wygasle', $any);
        }

        // 6) Brak jakiegokolwiek A1.
        return ['status' => 'brak', 'a1' => null];
    }

    private function a1Row(string $status, $a1): array
    {
        return [
            'status' => $status,
            'a1' => [
                'id' => $a1->id,
                'start' => $a1->start,
                'end' => $a1->end,
                'kraj' => $a1->kraj->name ?? null,
            ],
        ];
    }
}
