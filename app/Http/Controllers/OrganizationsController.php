<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\JezykTyp;
use App\Models\KrajTyp;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class OrganizationsController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        $sort = request('sort', 'numerBud');
        $direction = request('direction') === 'desc' ? 'desc' : 'asc';

        // Mapowanie "publicznych" nazw sortów -> realne kolumny/aliasy SQL
        $allowedSorts = [
            'nazwaBud'           => 'organizations.nazwaBud',
            'numerBud'           => 'organizations.numerBud',
            'city'               => 'organizations.city',
            'has_active_workers' => 'has_active_workers',   // alias z addSelect
            'country'            => 'kt.name',              // join tylko przy tym sorcie
        ];

        if (!array_key_exists($sort, $allowedSorts)) {
            $sort = 'numerBud';
        }

        $query = Organization::query()
            ->with('krajTyp')
            ->addSelect([
                'has_active_workers' => ContactWorkDate::query()
                    ->selectRaw('1')
                    ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                    ->activeOn($today)
                    ->limit(1),

                'kierownicy_names' => ContactWorkDate::query()
                    ->join('contacts', 'contacts.id', '=', 'contact_work_dates.contact_id')
                    ->selectRaw(
                        "GROUP_CONCAT(DISTINCT CONCAT(contacts.last_name, ' ', contacts.first_name)
                     ORDER BY contacts.last_name SEPARATOR ', ')"
                    )
                    ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                    ->where('contacts.funkcja_id', 1),

                'inzynierowie_names' => ContactWorkDate::query()
                    ->join('contacts', 'contacts.id', '=', 'contact_work_dates.contact_id')
                    ->selectRaw(
                        "GROUP_CONCAT(DISTINCT CONCAT(contacts.last_name, ' ', contacts.first_name)
                     ORDER BY contacts.last_name SEPARATOR ', ')"
                    )
                    ->whereColumn('contact_work_dates.organization_id', 'organizations.id')
                    ->where('contacts.funkcja_id', 6),
            ])
            ->filter(Request::only('search', 'trashed'));

        if ($sort === 'country') {
            $query->leftJoin('kraj_typs as kt', 'kt.id', '=', 'organizations.country_id')
                ->addSelect('organizations.*')
                ->orderBy('kt.name', $direction);
        }
        $query->orderBy($allowedSorts[$sort], $direction);

        if ($sort !== 'numerBud') {
            $query->orderBy('organizations.numerBud', 'asc');
        }

        return Inertia::render('Organizations/Index', [
            'filters' => Request::all('search', 'trashed', 'sort', 'direction'),
            'organizations' => $query
                ->paginate(100)
                ->withQueryString()
                ->through(fn ($organization) => [
                    'id' => $organization->id,
                    'nazwaBud' => $organization->nazwaBud,
                    'numerBud' => $organization->numerBud,
                    'country' => $organization->krajTyp ? $organization->krajTyp : null,
                    'kierownicy' => $organization->kierownicy_names ?: null,
                    'inzynierowie' => $organization->inzynierowie_names ?: null,
                    'has_active_workers' => (bool) ($organization->has_active_workers ?? false),
                    'deleted_at' => $organization->deleted_at,
                ]),
        ]);
    }


    public function create()
    {
        return Inertia::render('Organizations/Create', [
            'krajTyps' => KrajTyp::all(),
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
        $org->country_id = $req->country_id;
        $org->addressBud = $req->addressBud;
        $org->addressKwat = $req->addressKwat;
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
        $flag = false;
        if (Auth::user()->owner === 3) {
            $flag = true;
        }
        return Inertia::render('Organizations/Edit', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'nazwaBud' => $organization->nazwaBud,
                'numerBud' => $organization->numerBud,
                'city' => $organization->city,
                'kierownikBud_id' => $organization->kierownikBud_id,
                'inzynier_id' => $organization->inzynier_id,
                'zaklad' => $organization->zaklad,
                'country_id' => $organization->country_id,
                'addressBud' => $organization->addressBud,
                'addressKwat' => $organization->addressKwat,
                'deleted_at' => $organization->deleted_at,
//                'contacts' => $organization->contacts()->funkcja()->orderByName()->get()->map->only('id', 'last_name', 'position', 'phone', 'name'),
            ],
            'krajTyps' => KrajTyp::all(),
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
            'flag' => $flag,
        ]);
    }

    public function update(Organization $organization)
    {
        $organization->update(
            Request::validate([
                'name' => ['required', 'max:100'],
                'nazwaBud' => ['nullable', 'max:2250'],
                'numerBud' => ['nullable', 'max:550'],
                'city' => ['nullable', 'max:150'],
                'kierownikBud_id' => ['nullable', 'max:25'],
                'inzynier_id' => ['nullable', 'max:25'],
                'zaklad' => ['nullable', 'max:2000'],
                'country_id' => ['nullable', 'max:25'],
                'addressBud' => ['nullable', 'max:2000'],
                'addressKwat' => ['nullable', 'max:2500'],
            ])
        );

        return Redirect::back()->with('success', 'Budowa poprawiona.');
    }

    public function destroy(Organization $organization)
    {
        $checkWorker = ContactWorkDate::where('organization_id', $organization->id)->get();
        if (count($checkWorker) > 0) {
            return Redirect::back()->with('error', 'Budowa nie została usunięta, najpierw proszę usunąć pracowników przypisanych do budowy');
        }
        $organization->delete();

        return Redirect::back()->with('success', 'Budowa usunięta.');
    }

    public function restore(Organization $organization)
    {
        $organization->restore();

        return Redirect::back()->with('success', 'Budowa przywrócona.');
    }
}
