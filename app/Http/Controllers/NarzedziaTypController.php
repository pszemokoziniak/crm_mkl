<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNarzedziaRequest;
use App\Http\Requests\StorePosRequest;
use App\Models\NarzedziaTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class NarzedziaTypController extends Controller
{
    public function index()
    {
        $narzedziaTyp = NarzedziaTyp::all();
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
                'deleted_at' => $narzedziaTyp->deleted_at,
            ],
        ]);
    }
    public function update(NarzedziaTyp $narzedziaTyp)
    {
        $narzedziaTyp->update(
            \Illuminate\Support\Facades\Request::validate([
                'name' => ['required', 'max:100'],
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
        return Inertia('NarzedziaTyp/Create');
    }
    public function store(StoreNarzedziaRequest $req)
    {
        NarzedziaTyp::create($req->validated());
        return Redirect::route('narzedziaTyp')->with('success', 'Zapisano.');
    }
}
