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
                ]),
        ]);
    }
    public function create(Organization $organization) {

        $toolsFree = Narzedzia::where('ilosc_all', '>', 0)->get()->map->only('id', 'name', 'ilosc_all', 'ilosc_magazyn');


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
                $data->narzedzia_nb = (integer) $request->ilosc[(integer) $item];
                $data->save();

                $data = Narzedzia::find((integer)$item);
                $data->ilosc_magazyn = (integer) $data->ilosc_magazyn - (integer) $request->ilosc[(integer) $item];
                $data->ilosc_budowa = (integer) $data->ilosc_budowa + (integer) $request->ilosc[(integer) $item];
                $data->save();

            }

        }
        return Redirect::route('budowy.narzedzia', $organization->id)->with('success', 'Sprzęt dodany');
    }
    public function destroy(Organization $organization, ToolWorkDate $toolWorkDate)
    {
        $data = Narzedzia::find($toolWorkDate->narzedzia_id);
        $data->ilosc_magazyn = (integer) $data->ilosc_magazyn + (integer) $toolWorkDate->narzedzia_nb;
        $data->ilosc_budowa = (integer) $data->ilosc_budowa - (integer) $toolWorkDate->narzedzia_nb;
        $data->save();

        $toolWorkDate->delete();

        return Redirect::route('budowy.narzedzia', $organization->id)->with('success', 'Usunięto.');
    }
}
