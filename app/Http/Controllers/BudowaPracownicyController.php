<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyBudowaPracownicyRequest;
use App\Http\Requests\FindPracownicyRequest;
use App\Http\Requests\StoreBudowaPracownicyRequest;
use App\Http\Requests\StoredestroyStoreRequest;
use App\Models\A1;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\BuildingTimeSheet;
use App\Models\Funkcja;
use App\Models\Organization;
use Carbon\Carbon;
//use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class BudowaPracownicyController extends Controller
{

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
        foreach ($request->checkedValues as $item) {
            $data = new ContactWorkDate;
            $data->contact_id = $item;
            $data->organization_id = $organization->id;
            $data->start = $request->start;
            $data->end = $request->end;
            $data->save();
        }
        return Redirect::route('pracownicy.create', $organization->id)->with('success', 'Pracownik dodany');
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
        $contactsBusy = DB::table('contact_work_dates')

            ->select('id', 'contact_id')
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
//            ($item->funkcja_id === 1)?:array_push($contactArray, $item->id);
        }
        $contactFreeArray = array_diff($contactArray, $contactsBusyArray);
        $contactFree = Contact::join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->select('contacts.id', 'contacts.first_name', 'contacts.last_name', 'contacts.phone', 'funkcjas.name as fn_name', 'contacts.funkcja_id')
            ->whereIn('contacts.id', $contactFreeArray)
            ->orderBy('contacts.last_name', 'asc')
            ->get();

        $workers = $this->organizationWorkers($organization->id);

        return Inertia::render('Pracownicy/Create', [
            'contactsFree' => $contactFree,
            'contacts' => $workers,
            'organization' => $organization,
        ]);
    }
}
