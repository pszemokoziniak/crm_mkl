<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosRequest;
use App\Models\KrajTyp;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;

class KrajTypController extends Controller
{
    public function index(): Response
    {
        return Inertia('KrajTyp/Index', ['krajTypes' => KrajTyp::orderBy('name')->get()]);
    }

    public function edit(KrajTyp $krajTyp): Response
    {
        return Inertia::render('KrajTyp/Edit', [
            'sposoby183' => KrajTyp::SPOSOBY_183,
            'kraj' => [
                'id' => $krajTyp->id,
                'name' => $krajTyp->name,
                'wymaga_a1' => $krajTyp->wymaga_a1,
                'sposob_183' => $krajTyp->sposob_183,
                'deleted_at' => $krajTyp->deleted_at,
            ],
            'feasts' => $krajTyp->getAttribute('feasts')
        ]);
    }

    public function update(KrajTyp $krajTyp)
    {
        $krajTyp->update(
            \Illuminate\Support\Facades\Request::validate([
                'name' => ['required', 'max:100'],
                // Znacznik decyduje o alertach o braku A1 i o zakładce przy budowie.
                'wymaga_a1' => ['required', 'boolean'],
                // Rok podatkowy czy każde ruchome 12 miesięcy — zależy od kraju.
                // "sometimes": zapis, który tego pola nie rusza, zostawia je bez zmian.
                'sposob_183' => ['sometimes', Rule::in(array_keys(KrajTyp::SPOSOBY_183))],
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
