<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Contact;
use App\Models\Account;
use App\Models\Funkcja;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BadaniaController extends Controller
{
    public function index(Contact $contact)
    {
//        dd($contact);
        return Inertia::render('Badania/Index', [
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
    public function edit(Contact $contact)
    {
        return Inertia::render('Badania/Edit', [
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
            // 'funkcja' => Funkcja::find($contact->funkcja),
        ]);
    }

}
