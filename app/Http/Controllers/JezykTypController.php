<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosRequest;
use App\Models\JezykTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class JezykTypController extends Controller
{
    public function index()
    {
        $jezykTypes = JezykTyp::orderBy('name')->all();
        return Inertia('JezykTyp/Index', compact('jezykTypes'));
    }

    public function edit(JezykTyp $jezykTyp)
    {
        return Inertia::render('JezykTyp/Edit', [
            'jezyk' => [
                'id' => $jezykTyp->id,
                'name' => $jezykTyp->name,
                'deleted_at' => $jezykTyp->deleted_at,
            ],
        ]);
    }

    public function update(JezykTyp $jezykTyp)
    {
        $jezykTyp->update(
            \Illuminate\Support\Facades\Request::validate([
                'name' => ['required', 'max:100'],
            ])
        );
        return Redirect::route('jezykTyp')->with('success', 'Poprawiono.');
    }

    public function destroy(JezykTyp $jezykTyp)
    {
        $jezykTyp->delete();

        return Redirect::route('jezykTyp')->with('success', 'Usunięto.');
    }

    public function restore(JezykTyp $jezykTyp)
    {
        $jezykTyp->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create()
    {
        return Inertia('JezykTyp/Create');
    }

    public function store(StorePosRequest $req)
    {
        JezykTyp::create($req->validated());
        return Redirect::route('jezykTyp')->with('success', 'Zapisano.');
    }
}
