<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindPracownicyRequest;
use App\Http\Requests\StoreBudowaPracownicyRequest;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\BuildingTimeSheet;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class BudowaPracownicyController extends Controller
{
    private const MULTI_SITE_FUNKCJA_IDS = [
        1, // Kierownik - przykładowe ID
        6, // Inżynier  - przykładowe ID
    ];

    public function organizationWorkers($id) {
        $workers = DB::table('contact_work_dates', 'cwd')
            ->select('cwd.id', 'cwd.contact_id', 'contacts.first_name', 'contacts.last_name', 'cwd.organization_id', 'cwd.start', 'cwd.end', 'funkcjas.name')
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

        if (!empty($request->manager_id)) {
            $toAssign[] = (int) $request->manager_id;
        }

        if (!empty($request->engineer_id)) {
            $toAssign[] = (int) $request->engineer_id;
        }

        foreach (($request->checkedValues ?? []) as $id) {
            $toAssign[] = (int) $id;
        }

        $toAssign = array_values(array_unique(array_filter($toAssign)));

        if (empty($toAssign)) {
            return Redirect::back()->with('error', 'Nie wybrano żadnego pracownika.');
        }

        $multiSiteFunkcjaIds = self::MULTI_SITE_FUNKCJA_IDS;

        $nonSpecialIds = Contact::query()
            ->whereIn('id', $toAssign)
            ->whereNotIn('funkcja_id', $multiSiteFunkcjaIds)
            ->pluck('id')
            ->all();

        if (!empty($nonSpecialIds)) {
            $busyIds = DB::table('contact_work_dates')
                ->whereIn('contact_id', $nonSpecialIds)
                ->where('start', '<=', $end)
                ->where('end', '>=', $start)
                ->distinct()
                ->pluck('contact_id')
                ->all();

            if (!empty($busyIds)) {
                $busyNames = Contact::query()
                    ->whereIn('id', $busyIds)
                    ->orderBy('last_name')
                    ->get(['first_name', 'last_name'])
                    ->map(fn ($c) => $c->last_name . ' ' . $c->first_name)
                    ->implode(', ');

                return Redirect::back()->with(
                    'error',
                    'Niedostępni w tym terminie: ' . ($busyNames ?: 'wybrani pracownicy') . '.'
                );
            }
        }

        DB::transaction(function () use ($toAssign, $organization, $start, $end) {
            foreach ($toAssign as $contactId) {

                $exists = ContactWorkDate::query()
                    ->where('contact_id', $contactId)
                    ->where('organization_id', $organization->id)
                    ->where('start', $start)
                    ->where('end', $end)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $data = new ContactWorkDate();
                $data->contact_id = $contactId;
                $data->organization_id = $organization->id;
                $data->start = $start;
                $data->end = $end;
                $data->save();
            }
        });

        return Redirect::route('pracownicy.create', $organization->id)
            ->with('success', 'Pracownicy dodani.');
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
        $contactWorkDate->delete();

        BuildingTimeSheet::where('contact_id', $contactWorkDate->contact_id)
            ->where('organization_id', $contactWorkDate->organization_id)
            ->delete();

        return Redirect::route('pracownicy.index', $contactWorkDate->organization_id)->with('success', 'Usunięto.');
    }

    public function find(FindPracownicyRequest $request, Organization $organization)
    {
        $start = $request->start;
        $end   = $request->end;

        // 1) Wyciągamy kontakty specjalne (kierownik+inżynier) bez ograniczeń dostępności
        $specialists = Contact::query()
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->select([
                'contacts.id',
                'contacts.first_name',
                'contacts.last_name',
                'contacts.phone',
                'contacts.funkcja_id',
                'funkcjas.name as fn_name',
            ])
            ->whereIn('contacts.funkcja_id', self::MULTI_SITE_FUNKCJA_IDS)
            ->orderBy('contacts.last_name', 'asc')
            ->get();

        // 2) Wyciągamy zajętych w oknie [start,end] (overlap)
        $busyContactIds = DB::table('contact_work_dates')
            ->where('start', '<=', $end)
            ->where('end', '>=', $start)
            ->distinct()
            ->pluck('contact_id')
            ->all();

        // 3) Pozostali: nie-specjalni i wolni
        $contactsFree = Contact::query()
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->select([
                'contacts.id',
                'contacts.first_name',
                'contacts.last_name',
                'contacts.phone',
                'contacts.funkcja_id',
                'funkcjas.name as fn_name',
            ])
            ->whereNotIn('contacts.funkcja_id', self::MULTI_SITE_FUNKCJA_IDS)
            ->whereNotIn('contacts.id', $busyContactIds)
            ->orderBy('contacts.last_name', 'asc')
            ->get();

        $workers = $this->organizationWorkers($organization->id);

        return Inertia::render('Pracownicy/Create', [
            'specialists'  => $specialists,
            'contactsFree' => $contactsFree,
            'contacts'     => $workers,
            'organization' => $organization,
            'start'        => $start,
            'end'          => $end,
        ]);
    }
}
