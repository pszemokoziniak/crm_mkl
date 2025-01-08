<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosRequest;
use App\Models\BadaniaTyp;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class BadaniaTypController extends Controller
{
    public function index()
    {
        $badaniaTypes = BadaniaTyp::orderBy('name')->get();
        return Inertia('BadaniaTyp/Index', compact('badaniaTypes'));
    }

    public function edit(BadaniaTyp $badaniaTyp)
    {
        return Inertia::render('BadaniaTyp/Edit', [
            'badania' => [
                'id' => $badaniaTyp->id,
                'name' => $badaniaTyp->name,
                'deleted_at' => $badaniaTyp->deleted_at,
            ],
        ]);
    }

    public function update(BadaniaTyp $badaniaTyp)
    {
        $badaniaTyp->update(
            Request::validate([
                'name' => ['required', 'max:50'],
            ])
        );
        return Redirect::route('badaniaTyp')->with('success', 'Poprawiono.');
    }

    public function destroy(BadaniaTyp $badaniaTyp)
    {
        $badaniaTyp->delete();

        // return Redirect::back()->with('success', 'Objekt usunięty.');
        return Redirect::route('badaniaTyp')->with('success', 'Usunięto.');
    }

    public function restore(BadaniaTyp $badaniaTyp)
    {
        $badaniaTyp->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create()
    {
        return Inertia('BadaniaTyp/Create');
    }

    public function store(StorePosRequest $req)
    {
        BadaniaTyp::create($req->validated());
        return Redirect::route('badaniaTyp')->with('success', 'Zapisano.');
    }
}
