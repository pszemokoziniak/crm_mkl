<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Account;
use App\Models\Funkcja;

use App\Models\Jezyk;
use App\Models\Organization;
use http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ContactsController extends Controller
{
    public function index()
    {
        return Inertia::render('Contacts/Index', [
            'filters' => Request::all('search', 'trashed'),
            'contacts' => Contact::with('funkcja')
                ->orderByName()
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($contact) => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'phone' => $contact->phone,
                    'city' => $contact->city,
                    'deleted_at' => $contact->deleted_at,
                    'funkcja' => $contact->funkcja,
                ]),
        ]);
    }

    public function create()
    {

        return Inertia::render('Contacts/Create', [
            'organizations' => Auth::user()->account
                ->organizations()
                ->orderBy('name')
                ->get()
                ->map
                ->only('id', 'name'),
            'accounts' => Auth::user()->account
                ->accounts()
                ->map
                ->only('id', 'name'),
            'funkcjas' => Funkcja::all(),
        ]);
    }

    public function store()
    {
        Auth::user()->account->contacts()->create(
            Request::validate([
                'first_name' => ['required', 'max:150'],
                'last_name' => ['required', 'max:150'],
                'birth_date' => ['required'],
                'pesel' => ['required'],
                'idCard_number' => ['required'],
                'idCard_date' => ['required'],
                'position' => ['required'],
                'funkcja_id' => ['required'],
                'work_start' => ['required'],
                'work_end' => ['required'],
                'ekuz' => ['required'],
                'organization_id' => ['nullable'],

//                'organization_id' => ['nullable', Rule::exists('organizations', 'id')->where(function ($query) {
//                    $query->where('account_id', Auth::user()->account_id);
//                })],
                'email' => ['nullable', 'max:150', 'email'],
                'phone' => ['nullable', 'max:150'],
                'address' => ['nullable', 'max:150'],
            ])
        );

        return Redirect::route('contacts')->with('success', 'Pracownik stworzony');
    }

    public function edit(Contact $contact)
    {
        return Inertia::render('Contacts/Edit', [
            'contact' => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'organization_id' => $contact->organization_id,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'address' => $contact->address,
                'birth_date' => $contact->birth_date,
                'pesel' => $contact->pesel,
                'idCard_number' => $contact->idCard_number,
                'idCard_date' => $contact->idCard_date,
                'position' => $contact->position,
                'funkcja_id' => $contact->funkcja_id,
                'work_start' => $contact->work_start,
                'work_end' => $contact->work_end,
                'ekuz' => $contact->ekuz,

                // 'city' => $contact->city,
                // 'region' => $contact->region,
                // 'country' => $contact->country,
                // 'postal_code' => $contact->postal_code,
                'deleted_at' => $contact->deleted_at,
            ],
            'organizations' => Auth::user()->account->organizations()
                ->orderBy('name')
                ->get()
                ->map
                ->only('id', 'name'),
                'accounts' => Auth::user()->account
                ->accounts()
                ->map
                ->only('id', 'name'),
            'funkcjas' => Funkcja::all(),
//            'jezyks' => Jezyk::where('contact_id', $contact->id)->get(),
            'jezyks' => Jezyk::with('jezykTyp')
                ->where('contact_id', $contact->id)
                ->orderByName()
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($jezyk) => [
                    'id' => $jezyk->id,
                    'poziom' => $jezyk->poziom,
                    'jezyk' => $jezyk->jezykTyp ? $jezyk->jezykTyp : null,
                ]),
            // 'funkcja' => Funkcja::find($contact->funkcja),
        ]);
    }

    public function update(Contact $contact)
    {
//        dd($contact->funkcja_id);

        $contact->update(
            Request::validate([
                'first_name' => ['required', 'max:50'],
                'last_name' => ['required', 'max:50'],
                'pesel' => ['required', 'max:50'],
                'organization_id' => ['nullable', 'max:50'],
//                'organization_id' => [
//                    'nullable',
//                    Rule::exists('organizations', 'id')->where(fn ($query) => $query->where('account_id', Auth::user()->account_id)),
//                ],
                'email' => ['nullable', 'max:50', 'email'],
                'phone' => ['nullable', 'max:50'],
                'address' => ['nullable', 'max:150'],
                'city' => ['nullable', 'max:50'],
                'region' => ['nullable', 'max:50'],
                'country' => ['nullable', 'max:2'],
                'postal_code' => ['nullable', 'max:25'],
                'birth_date' => ['nullable', 'date'],
                'idCard_number' => ['nullable', 'max:50'],
                'idCard_date' => ['nullable', 'date'],
                'position' => ['nullable', 'max:25'],
                'work_start' => ['nullable', 'date'],
                'work_end' => ['nullable', 'date'],
                'ekuz' => ['nullable', 'max:25'],
                'funkcja_id' => ['nullable', 'max:50'],
            ])
        );

        return Redirect::back()->with('success', 'Pracownik poprawiony.');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return Redirect::back()->with('success', 'Pracownik usunięty.');
    }

    public function restore(Contact $contact)
    {
        $contact->restore();

        return Redirect::back()->with('success', 'Pracownik przywrócony.');
    }

    public function storePracownik(Request $request, Organization $organization)
    {
        foreach ($request::all() as $item) {
            $data = Contact::find($item);
            $data->organization_id = $organization->id;
            $data->save();
        }
        return Redirect::back()->with('success', 'Pracownik dodany.');
    }
}
