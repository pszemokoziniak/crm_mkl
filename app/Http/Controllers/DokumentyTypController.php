<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDokumentTypRequest;
use App\Http\Requests\StorePosRequest;
use App\Models\DokumentyTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class DokumentyTypController extends Controller
{
    public function index()
    {
        $dokumentyTyps = DokumentyTyp::all();
        return Inertia('DokumentyTyp/Index', compact('dokumentyTyps'));
    }

    public function edit(DokumentyTyp $dokumentyTyp)
    {
        return Inertia::render('DokumentyTyp/Edit', [
            'dokumentTyp' => [
                'id' => $dokumentyTyp->id,
                'name' => $dokumentyTyp->name,
                'deleted_at' => $dokumentyTyp->deleted_at,
            ],
        ]);
    }

    public function destroy(DokumentyTyp $dokumentyTyp)
    {
        $dokumentyTyp->delete();

        return Redirect::route('dokumentyTyp')->with('success', 'Usunięto.');
    }

    public function restore(DokumentyTyp $account)
    {
        $account->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }

    public function update(DokumentyTyp $dokumentyTyp)
    {
        $dokumentyTyp->update(
            \Illuminate\Support\Facades\Request::validate([
                'name' => ['required', 'max:50'],
            ])
        );

        return Redirect::route('dokumentyTyp')->with('success', 'Poprawiono.');
    }

    public function create()
    {
        return Inertia('DokumentyTyp/Create');
    }

    public function store(StoreDokumentTypRequest $req)
    {
        DokumentyTyp::create($req->validated());
        return Redirect::route('dokumentyTyp')->with('success', 'Zapisano.');
    }
}
