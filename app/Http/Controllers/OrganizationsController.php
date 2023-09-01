<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\JezykTyp;
use App\Models\KrajTyp;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class OrganizationsController extends Controller
{
    public function index()
    {
        if (!auth()->user()->permissions['kierownik']) {
            abort(403);
        }

        return Inertia::render('Organizations/Index', [
            'filters' => Request::all('search', 'trashed'),
            'organizations' => Organization::with('krajTyp')
                ->orderBy('name')
                ->filter(Request::only('search', 'trashed'))
                ->paginate(100)
                ->withQueryString()
                ->through(fn ($organization) => [
                    'id' => $organization->id,
                    'nazwaBud' => $organization->nazwaBud,
                    'country' => $organization->krajTyp ? $organization->krajTyp : null,
                    'kierownikBud_id' => $organization->contactTypName ? $organization->contactTypName : null,
                    'deleted_at' => $organization->deleted_at,
                ]),
        ]);
    }

    public function create()
    {
        if (!auth()->user()->permissions['kierownik']) {
            abort(403);
        }
        return Inertia::render('Organizations/Create', [
            'krajTyps' => KrajTyp::all(),
            'kierownikBud' => Contact::where('funkcja_id', '=', 1)->get(),
        ]);
    }

    public function store(StoreOrganizationRequest $req)
    {
        $data = new Organization;
        $data->name=$req->name;
        $data->account_id=0;
        $data->nazwaBud=$req->nazwaBud;
        $data->numerBud=$req->numerBud;
        $data->city=$req->city;
        $data->kierownikBud_id=$req->kierownikBud_id;
        $data->zaklad=$req->zaklad;
        $data->country_id=$req->country_id;
        $data->addressBud=$req->addressBud;
        $data->addressKwat=$req->addressKwat;
        $data->save();

//        Auth::user()->account->organizations()->create(
//            Request::validate([
//                'name' => ['required', 'max:1200'],
//                'nazwaBud' => ['nullable', 'max:1200'],
//                'numerBud' => ['nullable', 'max:50'],
//                'city' => ['nullable', 'max:2000'],
//                'kierownikBud_id' => ['nullable', 'max:50'],
//                'zaklad' => ['nullable', 'max:50'],
//                'country_id' => ['nullable', 'max:1000'],
//                'addressBud' => ['nullable', 'max:25'],
//                'addressKwat' => ['nullable', 'max:25'],
//            ])
//        );

        return Redirect::route('organizations')->with('success', 'Budowa stworzona.');
    }

    public function edit(Organization $organization)
    {
        if (!auth()->user()->permissions['kierownik']) {
            abort(403);
        }

        return Inertia::render('Organizations/Edit', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'nazwaBud' => $organization->nazwaBud,
                'numerBud' => $organization->numerBud,
                'city' => $organization->city,
                'kierownikBud_id' => $organization->kierownikBud_id,
                'zaklad' => $organization->zaklad,
                'country_id' => $organization->country_id,
                'addressBud' => $organization->addressBud,
                'addressKwat' => $organization->addressKwat,
                'deleted_at' => $organization->deleted_at,
//                'contacts' => $organization->contacts()->funkcja()->orderByName()->get()->map->only('id', 'last_name', 'position', 'phone', 'name'),
            ],
            'krajTyps' => KrajTyp::all(),
            'kierownikBud' => Contact::where('funkcja_id', '=', 1)->get(),
//            'contacts' => Contact::where('organization_id', $organization->id)->get(),
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
