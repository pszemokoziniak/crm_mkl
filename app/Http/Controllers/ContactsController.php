<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoreCustomersRequest;
use App\Models\A1;
use App\Models\Badania;
use App\Models\Bhp;
use App\Models\BuildingTimeSheet;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Jezyk;
use App\Models\Organization;
use App\Models\Pbioz;
use App\Models\Uprawnienia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class ContactsController extends Controller
{
    public function index()
    {
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
                    'pracuje' => $this->findPresentBuild($contact->id),
                    'status_zatrudnienia' => $contact->status_zatrudnienia,
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

    public function store(StoreCustomersRequest $request)
    {
        Auth::user()->account->contacts()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'birth_date' => $request->birth_date,
            'pesel' => $request->pesel,
            'idCard_number' => $request->idCard_number,
            'idCard_date' => $request->idCard_date,
            'funkcja_id' => $request->funkcja_id,
            'work_start' => $request->work_start,
            'work_end' => $request->work_end,
            'ekuz' => $request->ekuz,
            'miejsce_urodzenia' => $request->miejsce_urodzenia,
            'organization_id' => $request->organization_id,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'photo_path' => $request->file('photo_path') ? $request->file('photo_path')->store('contacts') : null,
            'status_zatrudnienia' => $request->status_zatrudnienia,
        ]);


        return Redirect::route('contacts')->with('success', 'Pracownik stworzony');
    }

    public function edit(Contact $contact)
    {
        $obecna_budowa = ContactWorkDate::with('organization')
            ->where('contact_id', $contact->id)
            ->where('end', '>', Carbon::now())
            ->where('start', '<=', Carbon::now())
            ->first();

        if (!$obecna_budowa) {
            $obecna_budowa = 'Nie pracuje';
        }

        $flag = false;
        if (Auth::user()->owner === 3) {
            $flag = true;
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
                'funkcja_id' => $contact->funkcja_id,
                'work_start' => $contact->work_start,
                'work_end' => $contact->work_end,
                'ekuz' => $contact->ekuz,
                'miejsce_urodzenia' => $contact->miejsce_urodzenia,
                'photo_path' => $contact->photo_path ? URL::route('image', ['path' => $contact->photo_path, 'w' => 260, 'h' => 260, 'fit' => 'crop', 'fm' => 'jpg']) : null,
                'deleted_at' => $contact->deleted_at,
                'status_zatrudnienia' => $contact->status_zatrudnienia,
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

            'bhp' => Bhp::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'lekarskie' => Badania::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'a1' => A1::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'uprawnienia' => Uprawnienia::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'pbioz' => Pbioz::select('start', 'end')->where('contact_id', $contact->id)->latest()->get()->map->only('end'),
            'obecna_budowa' => $obecna_budowa,
            'flag' => $flag,
            'user_owner' => Auth::user()->owner,
        ]);
    }

    public function update(Contact $contact, StoreContactRequest $request)
    {
        $data = $request->only('first_name', 'last_name', 'birth_date', 'pesel', 'idCard_number', 'idCard_date', 'funkcja_id', 'work_start',
            'work_end', 'ekuz', 'miejsce_urodzenia', 'organization_id', 'email', 'phone', 'address', 'status_zatrudnienia');

        if ($request->hasFile('photo_path')) {
            $data['photo_path'] = $request->file('photo_path')->store('contacts');
        }

        $contact->update($data);

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

    public function findPresentBuild($id) {

        $dateToday = Carbon::today()->format('Y-m-d');
        $data = ContactWorkDate::with('organization')
            ->where('contact_id', $id)
            ->where(function ($query) use ($dateToday){
                $query->where('start', '<=', $dateToday);
            })
            ->where(function ($query) use ($dateToday){
                $query->where('end', '>=', $dateToday);
            })
            ->latest()->get()->pluck('organization.nazwaBud')->toArray();

        if(!$data) {
            return "Nie pracuje";
        }
        return $data[0];
    }

    public function history(Contact $contact)
    {
        $history = BuildingTimeSheet::with('build')
            ->where('contact_id', $contact->id)
            ->orderBy('work_day', 'desc')
            ->get()
            ->groupBy('organization_id')
            ->map(function ($group) {
                return [
                    'organization' => $group->first()->build->nazwaBud,
                    'start' => $group->min('work_day'),
                    'end' => $group->max('work_day'),
                    'hours' => $group->sum(function ($item) {
                        if (!$item->effective_work_time) return 0;
                        list($hours, $minutes) = explode(':', $item->effective_work_time);

                        return $hours + ($minutes / 60);
                    }),
                ];
            });

        return Inertia::render('Contacts/History', [
            'contact' => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
            ],
            'history' => $history->values(),
        ]);
    }
}
