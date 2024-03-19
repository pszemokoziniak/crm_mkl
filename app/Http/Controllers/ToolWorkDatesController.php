<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindPracownicyRequest;
use App\Http\Requests\StoreBudowaPracownicyRequest;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Narzedzia;
use App\Models\Organization;
use App\Models\ToolWorkDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ToolWorkDatesController extends Controller
{

    public function organizationWorkers($id) {
        $workers = DB::table('contact_work_dates', 'cwd')
            ->select('contacts.first_name', 'contacts.last_name', 'cwd.organization_id', 'cwd.start', 'cwd.end', 'funkcjas.name', 'funkcjas.id')
            ->join('contacts', 'cwd.contact_id', '=', 'contacts.id')
            ->join('funkcjas', 'contacts.funkcja_id', '=', 'funkcjas.id')
            ->where('cwd.organization_id', $id)
            ->orderBy('last_name')
            ->get();

        return $workers;
    }

    public function index(Organization $organization)
    {

        return Inertia::render('NarzedziaBudowa/Index', [
            'organization' => $organization,
            'filters' => \Illuminate\Support\Facades\Request::all('search', 'trashed'),
            'toolsOnBuild' => ToolWorkDate::with('narzedzia')
                ->where('organization_id', $organization->id)
                ->paginate(100)
                ->withQueryString()
                ->through(fn ($item) => [
                    'id' => $item->id,
                    'organization_id' => $item->organization_id,
                    'narzedzia_nb' => $item->narzedzia_nb,
                    'narzedzia' => $item->narzedzia,
                    'start' => $item->start,
                    'end' => $item->end,
                ]),
        ]);
    }
    public function create(Organization $organization) {

        $toolsFree = Narzedzia::where('ilosc_all', '>', 0)->get()->map->only('id', 'name', 'ilosc');


        return Inertia::render('NarzedziaBudowa/Create', [
            'toolsFree' => $toolsFree,
            'organization' => $organization,
            'toolsOnBuild' => ToolWorkDate::with('narzedzia')
                ->where('organization_id', $organization->id)
                ->paginate(100)
                ->withQueryString()
                ->through(fn ($item) => [
                    'id' => $item->id,
                    'organization_id' => $item->organization_id,
                    'narzedzia_nb' => $item->narzedzia_nb,
                    'narzedzia' => $item->narzedzia,
                    'start' => $item->start,
                    'end' => $item->end,
                ]),
        ]);
    }
    public function store(Request $request, Organization $organization)
    {
        foreach ($request->checkedValues as $item) {

            if ((integer)$request->ilosc[(integer) $item] !== null || (integer)$request->ilosc[(integer) $item] !== 0) {

                $data = new ToolWorkDate;
                $data->narzedzia_id = (integer)$item;
                $data->organization_id = $organization->id;
                $data->start = $request->start;
                $data->end = $request->end;
                $data->narzedzia_nb = (integer) $request->ilosc[(integer) $item];
                $data->save();
            }
        }
        return Redirect::route('budowy.narzedzia', $organization->id)->with('success', 'Narzędzia dodane');
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

    public function destroy(Organization $organization, ToolWorkDate $toolWorkDate)
    {
        $toolWorkDate->delete();

        return Redirect::route('budowy.narzedzia', $organization->id)->with('success', 'Usunięto.');
    }

    public function toolsBusy($request, $id) {

        $data = DB::table('tool_work_dates')

            ->select('narzedzia_id', DB::raw('SUM(narzedzia_nb) as total_narzedzia_nb'))
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

            ->groupBy('narzedzia_id')
            ->get()->where('narzedzia_id', $id)->first();

        if ($data === null)  {return 0;} else {return (integer) $data->total_narzedzia_nb;}

        return (integer) $data->total_narzedzia_nb;
    }
    public function find(Request $request, Organization $organization)
    {
        $toolsFree = array();
        $toolsAll = Narzedzia::where('ilosc_all', '>', 0)->get()->map->only('id', 'name', 'ilosc');

//        foreach ($toolsAll as $item) {
//            array_push($toolsFree, ['id' => $item['id'], 'name' => $item['name'], 'toll' => ((integer) $item['ilosc'] - (integer) $this->toolsBusy($request, $item['id'])) ]);
//        }

        return Inertia::render('NarzedziaBudowa/Create', [
            'toolsFree' => $toolsFree,
            'organization' => $organization,
            'toolsOnBuild' => ToolWorkDate::with('narzedzia')
                ->where('organization_id', $organization->id)
                ->paginate(100)
                ->withQueryString()
                ->through(fn ($item) => [
                    'id' => $item->id,
                    'organization_id' => $item->organization_id,
                    'narzedzia_nb' => $item->narzedzia_nb,
                    'narzedzia' => $item->narzedzia,
                    'start' => $item->start,
                    'end' => $item->end,
                ]),
        ]);
    }
}
