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
                ->filter(\Illuminate\Support\Facades\Request::only('search', 'trashed'))
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

        $toolsFree = Narzedzia::where(function($query) {
                $query->where('ilosc_all', '>', 0)
                      ->orWhereNull('ilosc_all');
            })
            ->get()
            ->map(function ($tool) {
                return [
                    'id' => $tool->id,
                    'name' => $tool->name,
                    'ilosc_all' => $tool->ilosc_all ?? 0,
                    'ilosc_magazyn' => $tool->ilosc_magazyn ?? $tool->ilosc_all ?? 0,
                ];
            });


        return Inertia::render('NarzedziaBudowa/Create', [
            'toolsFree' => $toolsFree,
            'organization' => $organization,
            'toolsOnBuild' => ToolWorkDate::with('narzedzia')
                ->where('organization_id', $organization->id)
                ->filter(\Illuminate\Support\Facades\Request::only('search', 'trashed'))
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
            $iloscDoDodania = (int) ($request->ilosc[(int) $item] ?? 0);

            if ($iloscDoDodania > 0) {
                // Szukamy czy to narzędzie już jest na tej budowie
                $toolOnBuild = ToolWorkDate::where('organization_id', $organization->id)
                    ->where('narzedzia_id', (int) $item)
                    ->first();

                if ($toolOnBuild) {
                    // Jeśli jest, zwiększamy ilość
                    $toolOnBuild->narzedzia_nb += $iloscDoDodania;
                    $toolOnBuild->save();
                } else {
                    // Jeśli nie ma, tworzymy nowy wpis
                    $data = new ToolWorkDate;
                    $data->narzedzia_id = (int) $item;
                    $data->organization_id = $organization->id;
                    $data->narzedzia_nb = $iloscDoDodania;
                    $data->save();
                }

                // Aktualizujemy stany w magazynie i na budowie (ogólne)
                $narzedzie = Narzedzia::find((int) $item);
                $narzedzie->ilosc_magazyn = ($narzedzie->ilosc_magazyn ?? $narzedzie->ilosc_all ?? 0) - $iloscDoDodania;
                $narzedzie->ilosc_budowa = ($narzedzie->ilosc_budowa ?? 0) + $iloscDoDodania;
                $narzedzie->save();
            }
        }
        return Redirect::route('budowy.narzedzia', $organization->id)->with('success', 'Sprzęt dodany');
    }

    public function edit(Organization $organization, ToolWorkDate $narzedzia)
    {
        return Inertia::render('NarzedziaBudowa/Edit', [
            'organization' => $organization,
            'toolWorkDate' => [
                'id' => $narzedzia->id,
                'narzedzia_nb' => $narzedzia->narzedzia_nb,
                'narzedzia' => $narzedzia->narzedzia,
            ],
            'narzedzie' => $narzedzia->narzedzia,
        ]);
    }

    public function update(Request $request, Organization $organization, ToolWorkDate $narzedzia)
    {
        $request->validate([
            'narzedzia_nb' => ['required', 'numeric', 'min:1'],
        ]);

        $nowaIlosc = (int) $request->narzedzia_nb;
        $staraIlosc = (int) $narzedzia->narzedzia_nb;
        $roznica = $nowaIlosc - $staraIlosc;

        // Aktualizujemy stany w magazynie i na budowie (ogólne)
        $narzedzie = Narzedzia::find($narzedzia->narzedzia_id);

        // Sprawdzamy czy mamy wystarczająco w magazynie jeśli zwiększamy
        $magazyn = $narzedzie->ilosc_magazyn ?? $narzedzie->ilosc_all ?? 0;
        if ($roznica > 0 && $magazyn < $roznica) {
            return Redirect::back()->with('error', 'Brak wystarczającej ilości w magazynie.');
        }

        $narzedzie->ilosc_magazyn = $magazyn - $roznica;
        $narzedzie->ilosc_budowa = ($narzedzie->ilosc_budowa ?? 0) + $roznica;
        $narzedzie->save();

        // Aktualizujemy ilość na tej budowie
        $narzedzia->narzedzia_nb = $nowaIlosc;
        $narzedzia->save();

        return Redirect::route('budowy.narzedzia', $organization->id)->with('success', 'Ilość zaktualizowana.');
    }

    public function destroy(Organization $organization, ToolWorkDate $toolWorkDate)
    {
        $data = Narzedzia::find($toolWorkDate->narzedzia_id);
        $data->ilosc_magazyn = (integer) ($data->ilosc_magazyn ?? $data->ilosc_all ?? 0) + (integer) $toolWorkDate->narzedzia_nb;
        $data->ilosc_budowa = (integer) ($data->ilosc_budowa ?? 0) - (integer) $toolWorkDate->narzedzia_nb;
        $data->save();

        $toolWorkDate->delete();

        return Redirect::route('budowy.narzedzia', $organization->id)->with('success', 'Usunięto.');
    }
}
