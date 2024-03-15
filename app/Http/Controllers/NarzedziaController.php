<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNarzedziaRequest;
use App\Models\Narzedzia;
use App\Models\Organization;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class NarzedziaController extends Controller
{
    public function index()
    {
        return Inertia::render('Narzedzia/Index', [
            'narzedzia' => Narzedzia::all()
        ]);
    }

    public function edit(Narzedzia $narzedzia)
    {
        return Inertia::render('Narzedzia/Edit', [
            'narzedzia' => [
                'id' => $narzedzia->id,
                'name' => $narzedzia->name,
                'numer_seryjny' => $narzedzia->numer_seryjny,
                'ilosc' => $narzedzia->ilosc,
                'deleted_at' => $narzedzia->deleted_at,
            ],
        ]);
    }

    public function update(Request $req, Narzedzia $narzedzia)
    {
        $data = Narzedzia::find($req->id);
        $data->name = $req->name;
        $data->ilosc = $req->ilosc;
        $data->save();

        return Redirect::phproute('narzedzia')->with('success', 'Element poprawiony.');
    }

    public function destroy(Narzedzia $narzedzia)
    {
        $narzedzia->delete();

        return Redirect::route('narzedzia')->with('success', 'Usunięto.');
    }

    public function restore(Narzedzia $narzedzia)
    {
        $narzedzia->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create()
    {
        return Inertia('Narzedzia/Create');
    }

    public function store(StoreNarzedziaRequest $req)
    {
        Narzedzia::create([
            'numer_seryjny' => Request::get('numer_seryjny'),
            'waznosc_badan' => Request::get('waznosc_badan'),
            'name' => Request::get('name'),
            'ilosc_all' => Request::get('ilosc_all'),
            'photo_path' => Request::file('photo') ? Request::file('photo')->store('narzedzias') : null,
            'filename' => Request::file('document') ? Request::file('document')->store('narzedzias') : null,
        ]);

        return Redirect::route('narzedzia')->with('success', 'Zapisano.');
    }

}
