<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyBudowaPracownicyRequest;
use App\Http\Requests\FindPracownicyRequest;
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
                ->paginate(100)
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
            $data->start = $_GET['start'];
            $data->end = $_GET['end'];
            $data->save();
        }
        return Redirect::route('pracownicy.create', $organization->id)->with('success', 'Pracownik stworzony');

    }

    public function find(FindPracownicyRequest $request, Organization $organization)
    {
        $contactsBusy = ContactWorkDate::query()
            ->select('contact_id')
            ->where(function ($query) use ($request){
               $query->where('start', '>=', $request->start)
                   ->where('end', '<=', $request->end);
           })
            ->orWhere(function ($query) use ($request){
                $query->where('start', '<=', $request->start)
                    ->where('end', '>=', $request->start);
            })
            ->orWhere(function ($query) use ($request){
                $query->where('start', '<=', $request->end)
                    ->where('end', '>=', $request->end);
            })
            ->distinct()
            ->get();

        $contactsBusyArray = array();
        $contactArray = array();

        foreach ($contactsBusy as $item) {
            array_push($contactsBusyArray, $item->contact_id);
        }

        $contacts = Contact::get();
        foreach ($contacts as $item) {
            array_push($contactArray, $item->id);
        }
        $contactFreeArray = array_diff($contactArray, $contactsBusyArray);

//        $contactFree = Contact::with('funkcja')
//            ->whereIn('id', $contactFreeArray)
//            ->get();

        return Inertia::render('Pracownicy/Create', [
            'contactsFree' => Contact::with('funkcja')
                ->whereIn('id', $contactFreeArray)
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


//        return Redirect::back()->with('success', 'Pracownik dodany.');
    }

    public function create(Organization $organization) {

        $contactsBusyArray = array();
        $contactArray = array();

        $contactsBusy = ContactWorkDate::where('end', NULL)->get();
        foreach ($contactsBusy as $item) {
            array_push($contactsBusyArray, $item->contact_id);
        }

        $contacts = Contact::get();
        foreach ($contacts as $item) {
            array_push($contactArray, $item->id);
        }
        $contactFreeArray = array_diff($contactArray, $contactsBusyArray);

        $contactFree = Contact::whereIn('id', $contactFreeArray)->get();

        return Inertia::render('Pracownicy/Create', [
//            'contactsFree' => Contact::where('organization_id', null)->orderByName()->get()->map->only('id','first_name','last_name'),
            'contactsFree' => $contactFree,
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

    public function destroyStore(DestroyBudowaPracownicyRequest $request)
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
