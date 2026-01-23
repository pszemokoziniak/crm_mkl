<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindPracownicyRequest;
use App\Http\Requests\StoreBudowaPracownicyRequest;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\BuildingTimeSheet;
use App\Models\Funkcja;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class BudowaPracownicyController extends Controller
{
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
            ->orderBy('last_name')
            ->get();
        return $workers;
    }

    public function index(Organization $organization)
    {

        return Inertia::render('Pracownicy/Index', [
            'organization_id' => $organization->id,
            'filters' => Request::all('search', 'trashed'),
            'contactworkdates' => ContactWorkDate::with('organization')
                ->with(['contact' => function ($query) {
                    $query->withTrashed();
                }])
                ->with('contact.funkcja')
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
        $management = DB::table('contact_work_dates', 'cwd')
            ->select('cwd.id', 'cwd.contact_id', 'contacts.first_name', 'contacts.last_name', 'cwd.start', 'cwd.end', 'funkcjas.name')
            ->join('contacts', 'cwd.contact_id', '=', 'contacts.id')
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->where('cwd.organization_id', $organization->id)
            ->whereIn('contacts.funkcja_id', self::MULTI_SITE_FUNKCJA_IDS)
            ->orderBy('last_name')
            ->get();

        $specialists = Contact::query()
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->select(['contacts.id', 'contacts.first_name', 'contacts.last_name', 'funkcjas.name as fn_name'])
            ->whereIn('contacts.funkcja_id', self::MULTI_SITE_FUNKCJA_IDS)
            ->where('contacts.status_zatrudnienia', '!=', Contact::STATUS_ZWOLNIONY)
            ->orderBy('last_name')
            ->get();

        return Inertia::render('Pracownicy/Kierownictwo', [
            'organization' => $organization,
            'management' => $management,
            'specialists' => $specialists,
        ]);
    }

    public function storeManagement(Organization $organization)
    {
        Request::validate([
            'contact_id' => ['required', 'exists:contacts,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

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
        $workerIds = ContactWorkDate::where('organization_id', $organization->id)
            ->distinct()
            ->pluck('contact_id');

        $workers = Contact::whereIn('id', $workerIds)
            ->with(['a1' => function ($q) {
                $q->latest()->with('kraj');
            }])
            ->when(Request::input('search'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhereHas('a1.kraj', function ($query) use ($search) {
                            $query->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByName()
            ->get()
            ->map(function ($contact) {
                $contact->latest_a1 = $contact->a1->sortByDesc('end')->first();
                return $contact;
            });

        return Inertia::render('Building/A1', [
            'build' => $organization->id,
            'buildDetails' => $organization,
            'workers' => $workers,
            'filters' => Request::all('search'),
        ]);
    }
}
