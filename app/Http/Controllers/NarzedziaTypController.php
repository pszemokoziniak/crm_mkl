<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosRequest;
use App\Models\NarzedziaTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class NarzedziaTypController extends Controller
{
    public function index()
    {
        // Kategoria najpierw — w magazynie typy i tak wiszą pod nią.
        $narzedziaTyp = NarzedziaTyp::orderBy('kategoria')->orderBy('name')->get();

        return Inertia('NarzedziaTyp/Index', [
            'narzedziaTyp' => $narzedziaTyp,
        ]);
    }

    public function edit(NarzedziaTyp $narzedziaTyp)
    {
        return Inertia::render('NarzedziaTyp/Edit', [
            'narzedziaTyp' => [
                'id' => $narzedziaTyp->id,
                'name' => $narzedziaTyp->name,
                'kategoria' => $narzedziaTyp->kategoria,
                'deleted_at' => $narzedziaTyp->deleted_at,
            ],
            'kategorie' => NarzedziaTyp::kategorie(),
        ]);
    }
    public function update(NarzedziaTyp $narzedziaTyp)
    {
        $narzedziaTyp->update(
            \Illuminate\Support\Facades\Request::validate([
                'name' => ['required', 'max:100'],
                'kategoria' => ['nullable', 'max:100'],
            ])
        );
        return Redirect::route('narzedziaTyp')->with('success', 'Poprawiono.');
    }
    public function destroy(NarzedziaTyp $narzedziaTyp)
    {
        $narzedziaTyp->delete();

        return Redirect::route('narzedziaTyp')->with('success', 'Usunięto.');
    }
    public function restore(NarzedziaTyp $narzedziaTyp)
    {
        $narzedziaTyp->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create()
    {
        return Inertia('NarzedziaTyp/Create', [
            'kategorie' => NarzedziaTyp::kategorie(),
        ]);
    }
    public function store()
    {
        // Własna walidacja: słownik typów nie ma nic wspólnego z formularzem
        // sprzętu, a wspólny StoreNarzedziaRequest wymagał tu wskazania typu
        // i przez to nie dawał założyć żadnego.
        $dane = \Illuminate\Support\Facades\Request::validate([
            'name' => ['required', 'max:100'],
            'kategoria' => ['nullable', 'max:100'],
        ], [
            'name.required' => 'Podaj nazwę typu.',
        ]);

        NarzedziaTyp::create([
            'name' => $dane['name'],
            'kategoria' => $dane['kategoria'] ?: null,
        ]);
        return Redirect::route('narzedziaTyp')->with('success', 'Zapisano.');
    }
}
