<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNarzedziaRequest;
use App\Models\Narzedzia;
use App\Models\Organization;
use Illuminate\Http\Request;
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

        return Redirect::route('narzedzia')->with('success', 'Element poprawiony.');
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
        Narzedzia::create($req->validated());
        return Redirect::route('narzedzia')->with('success', 'Zapisano.');
    }

}
