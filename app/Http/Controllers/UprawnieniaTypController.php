<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosRequest;
use App\Models\BadaniaTyp;
use App\Models\BhpTyp;
use App\Models\UprawnieniaTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class UprawnieniaTypController extends Controller
{
    public function index()
    {
        $uprawnieniaTypes = UprawnieniaTyp::orderBy('name')->get();
        return Inertia('UprawnieniaTyp/Index', compact('uprawnieniaTypes'));
    }

    public function edit(UprawnieniaTyp $uprawnieniaTyp)
    {
        return Inertia::render('UprawnieniaTyp/Edit', [
            'uprawnienia' => [
                'id' => $uprawnieniaTyp->id,
                'name' => $uprawnieniaTyp->name,
                'deleted_at' => $uprawnieniaTyp->deleted_at,
            ],
        ]);
    }

    public function update(UprawnieniaTyp $uprawnieniaTyp)
    {
        $uprawnieniaTyp->update(
            \Illuminate\Support\Facades\Request::validate([
                'name' => ['required', 'max:100'],
            ])
        );
        return Redirect::route('uprawnieniaTyp')->with('success', 'Poprawiono.');
    }

    public function destroy(UprawnieniaTyp $uprawnieniaTyp)
    {
        $uprawnieniaTyp->delete();

        return Redirect::route('uprawnieniaTyp')->with('success', 'Usunięto.');
    }

    public function restore(BadaniaTyp $badaniaTyp)
    {
        $badaniaTyp->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create()
    {
        return Inertia('UprawnieniaTyp/Create');
    }

    public function store(StorePosRequest $req)
    {
        UprawnieniaTyp::create($req->validated());
        return Redirect::route('uprawnieniaTyp')->with('success', 'Zapisano.');
    }
}
