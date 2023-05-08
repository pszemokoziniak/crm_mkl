<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomersRequest;
use App\Models\A1;
use App\Models\Badania;
use App\Models\Bhp;
use App\Models\Contact;
use App\Models\Account;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;

use App\Models\Jezyk;
use App\Models\Organization;
use App\Models\Pbioz;
use App\Models\Uprawnienia;
use Carbon\Carbon;
use http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ContactsController extends Controller
{
    public function index()
    {
        if (!auth()->user()->permissions['kierownik']) {
            abort(403);
        }

        return Inertia::render('Contacts/Index', [
            'filters' => Request::all('search', 'trashed'),
            'contacts' => Contact::with('funkcja')
                ->with('organization')
                ->orderByName()
                ->filter(Request::only('search', 'trashed'))
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
                    'budowa' => $contact->organization,
                    'a1' => A1::where('contact_id', $contact->id)->orderBy('end', 'desc')->first(),
                ]),
        ]);
    }

    public function create()
    {
        if (!auth()->user()->permissions['kierownik']) {
            abort(403);
        }

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

    public function store(StoreCustomersRequest $request)
    {
        Auth::user()->account->contacts()->create([
            'first_name' => Request::get('first_name'),
            'last_name' => Request::get('last_name'),
            'birth_date' => Request::get('birth_date'),
            'pesel' => Request::get('pesel'),
            'idCard_number' => Request::get('idCard_number'),
            'idCard_date' => Request::get('idCard_date'),
            'funkcja_id' => Request::get('funkcja_id'),
            'work_start' => Request::get('work_start'),
            'work_end' => Request::get('work_end'),
            'ekuz' => Request::get('ekuz'),
            'miejsce_urodzenia' => Request::get('miejsce_urodzenia'),
            'organization_id' => Request::get('organization_id'),
            'email' => Request::get('email'),
            'phone' => Request::get('phone'),
            'address' => Request::get('address'),
            'photo_path' => Request::file('photo_path') ? Request::file('photo_path')->store('contacts') : null,
        ]);

//                'organization_id' => ['nullable', Rule::exists('organizations', 'id')->where(function ($query) {
//                    $query->where('account_id', Auth::user()->account_id);
//                })],

        return Redirect::route('contacts')->with('success', 'Pracownik stworzony');
    }

    public function edit(Contact $contact)
    {
        if (!auth()->user()->permissions['kierownik']) {
            abort(403);
        }
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
//                'position' => $contact->position,
                'funkcja_id' => $contact->funkcja_id,
                'work_start' => $contact->work_start,
                'work_end' => $contact->work_end,
                'ekuz' => $contact->ekuz,
                'miejsce_urodzenia' => $contact->miejsce_urodzenia,
                'photo_path' => $contact->photo_path ? URL::route('image', ['path' => $contact->photo_path, 'w' => 260, 'h' => 260, 'fit' => 'crop']) : null,
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
//             'bhp' => BHP::where('contact_id', $contact->id)
//                 ->orderBy('end', 'desc')
//                 ->first(),
            'bhp' => Bhp::select('start', 'end')->where('contact_id', $contact->id)->where('end', '>=', Carbon::now())->where('start', '<=', Carbon::now())->get()->map->only('end'),
            'lekarskie' => Badania::select('start', 'end')->where('contact_id', $contact->id)->where('end', '>=', Carbon::now())->where('start', '<=', Carbon::now())->get()->map->only('end'),
            'a1' => A1::select('start', 'end')->where('contact_id', $contact->id)->where('end', '>=', Carbon::now())->where('start', '<=', Carbon::now())->get()->map->only('end'),
            'uprawnienia' => Uprawnienia::select('start', 'end')->where('contact_id', $contact->id)->where('end', '>=', Carbon::now())->where('start', '<=', Carbon::now())->get()->map->only('end'),
            'pbioz' => Pbioz::select('start', 'end')->where('contact_id', $contact->id)->where('end', '>', Carbon::now())->where('start', '<=', Carbon::now())->get()->map->only('end'),
            'obecna_budowa' => ContactWorkDate::with('organization')->where('contact_id', $contact->id)->where('end', '>', Carbon::now())->where('start', '<=', Carbon::now())->first(),

        ]);
    }

    public function update(Contact $contact, Request $request)
    {
//        dd($request);
            Request::validate([
                'first_name' => ['required', 'max:150'],
                'last_name' => ['required', 'max:150'],
                'birth_date' => ['required'],
                'pesel' => ['required'],
                'idCard_number' => ['nullable'],
                'idCard_date' => ['nullable'],
                'funkcja_id' => ['nullable'],
                'work_start' => ['required'],
                'work_end' => ['required'],
                'ekuz' => ['nullable'],
                'miejsce_urodzenia' => ['nullable'],
                'organization_id' => ['nullable'],
                'email' => ['nullable', 'max:150', 'email'],
                'phone' => ['nullable', 'max:50'],
                'address' => ['nullable'],
                'photo_path' => ['nullable', 'image'],
            ]);
        $contact->update(Request::only('first_name', 'last_name', 'birth_date', 'pesel', 'idCard_number', 'idCard_date', 'funkcja_id', 'work_start',
            'work_end', 'ekuz', 'miejsce_urodzenia', 'organization_id', 'email', 'phone', 'address'));

        if (Request::file('photo_path')) {
            $contact->update(['photo_path' => Request::file('photo_path')->store('contacts')]);
        }

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

            $data = new ContactWorkDate;
            $data->contact_id = $item;
            $data->organization_id = $organization->id;
//            $data->start = $request->start;
            $data->save();
        }


        return Redirect::back()->with('success', 'Pracownik dodany.');
    }

    public function destroyPracownikBudowa(Contact $contact)
    {
//        dd($contact);
        $data = Contact::find($contact->id);
        $data->organization_id = null;
        $data->save();
        return Redirect::back()->with('success', 'Pracownik usunięty.');
    }
}
