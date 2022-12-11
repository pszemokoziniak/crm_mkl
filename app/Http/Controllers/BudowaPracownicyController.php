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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class BudowaPracownicyController extends Controller
{

    public function listWorkers($organization) {
        $workers = DB::table('contact_work_dates')
            ->select('contact_work_dates.id as work_id', 'contacts.first_name', 'contacts.last_name', 'contact_work_dates.organization_id', 'contact_work_dates.start', 'contact_work_dates.end', 'funkcjas.name')
            ->join('contacts', 'contact_work_dates.contact_id', '=', 'contacts.id')
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->where('contact_work_dates.organization_id', $organization)
            ->get();
        return $workers;
    }

    public function index(Organization $organization)
    {
//        $workers = DB::table('contact_work_dates')
//            ->select('contacts.first_name', 'contacts.last_name', 'contact_work_dates.organization_id', 'contact_work_dates.start', 'contact_work_dates.end')
//            ->join('contacts', 'contact_work_dates.contact_id', '=', 'contacts.id')
//            ->where('contact_work_dates.organization_id', $organization->id)
//            ->where('contact_work_dates.start', '<=', Carbon::now()->format('Y-m-d'))
//            ->where('contact_work_dates.end', '>=', Carbon::now()->format('Y-m-d'))
//            ->get();

        $workers = $this->listWorkers($organization->id);
        return Inertia::render('Pracownicy/Index', [
            'organization_id' => $organization->id,
            'contacts' => $workers,
        ]);
    }
    public function create(Organization $organization) {

        $workers = $this->listWorkers($organization->id);

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
        return Redirect::route('pracownicy.create', $organization->id)->with('success', 'Pracownik stworzony');
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

        return Redirect::route('pracownicy.index', $contactWorkDate->organization_id)->with('success', 'Usunięto.');
    }

    public function find(FindPracownicyRequest $request, Organization $organization)
    {
        $contactsBusy = ContactWorkDate::query()
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
        }
        $contactFreeArray = array_diff($contactArray, $contactsBusyArray);
        $contactFree = Contact::join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->select('contacts.id', 'contacts.first_name', 'contacts.last_name', 'funkcjas.name as fn_name')
            ->whereIn('contacts.id', $contactFreeArray)
            ->get();
        $workers = $this->listWorkers($organization->id);
        return Inertia::render('Pracownicy/Create', [
            'contactsFree' => $contactFree,
            'contacts' => $workers,
            'organization' => $organization,
        ]);
    }
}
