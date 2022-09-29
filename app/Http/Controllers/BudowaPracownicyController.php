<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudowaPracownicyController extends Controller
{
    public function index(Organization $organization)
    {
        return Inertia::render('Pracownicy/Index', [
//            'filters' => Request::all('search', 'trashed'),
            'contacts' => Contact::with('funkcja')
                ->where('organization_id',$organization->id)
                ->orderByName()
//                ->filter(Request::only('search', 'trashed'))
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($contact) => [
                    'id' => $contact->id,
                    'name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'phone' => $contact->phone,
                    'email' => $contact->email,
                    'city' => $contact->city,
                    'deleted_at' => $contact->deleted_at,
                    'funkcja' => $contact->funkcja,
                ]),
        ]);
    }
}
