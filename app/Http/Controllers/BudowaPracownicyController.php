<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudowaPracownicyRequest;
use App\Http\Requests\StoredestroyStoreRequest;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
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
            'organization_id' => $organization->id,
        ]);
    }

    public function store(StoreBudowaPracownicyRequest $request, Organization $organization)
    {
        foreach ($request->checkedValues as $item) {
            $data = Contact::find($item);
            $data->organization_id = $organization->id;
            $data->save();

            $data = new ContactWorkDate;
            $data->contact_id = $item;
            $data->organization_id = $organization->id;
            $data->start = request()->start;
            $data->save();
        }

        return Redirect::back()->with('success', 'Pracownik dodany.');
    }

    public function create(Organization $organization) {
        return Inertia::render('Pracownicy/Create', [
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
            'organization' => $organization,
        ]);
    }

    public function destroy(Organization $organization, Contact $contact)
    {
        return Inertia::render('Pracownicy/Destroy', [
            'organization' => $organization,
            'contact' => Contact::with('funkcja')
                ->where('id', $contact->id)
                ->first(),
            'dates' => ContactWorkDate::with('organization')
                ->where('contact_id', $contact->id)
                ->where('organization_id', $organization->id)
                ->where('end', null)
                ->first(),
        ]);
    }

    public function destroyStore(Request $request)
    {
            // dd($request);
            $data = ContactWorkDate::find($request->id);
            $data->end = $request->end;
            $data->save();

            $data = Contact::find($request->contact_id);
            $data->organization_id = null;
            $data->save();


        return Redirect::route('pracownicy.create', [$request->organization_id])->with('success', 'Pracownik usunięty.');
    }
}
