<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosRequest;
use App\Models\KrajTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class KrajTypController extends Controller
{
    public function index()
    {
        $krajTypes = KrajTyp::all();
        return Inertia('KrajTyp', compact('krajTypes'));
    }

    public function edit(KrajTyp $krajTyp)
    {
        return Inertia::render('KrajTyp/Edit', [
            'kraj' => [
                'id' => $krajTyp->id,
                'name' => $krajTyp->name,
                'deleted_at' => $krajTyp->deleted_at,
            ],
        ]);
    }

    public function update(KrajTyp $krajTyp)
    {
        $krajTyp->update(
            \Illuminate\Support\Facades\Request::validate([
                'name' => ['required', 'max:100'],
            ])
        );
        return Redirect::route('krajTyp')->with('success', 'Poprawiono.');
    }

    public function destroy(KrajTyp $krajTyp)
    {
        $krajTyp->delete();

        return Redirect::route('krajTyp')->with('success', 'Usunięto.');
    }

    public function restore(KrajTyp $krajTyp)
    {
        $krajTyp->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create()
    {
        return Inertia('KrajTyp/Create');
    }

    public function store(StorePosRequest $req)
    {
        KrajTyp::create($req->validated());
        return Redirect::route('krajTyp')->with('success', 'Zapisano.');
    }
}
