<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosRequest;
use App\Models\BadaniaTyp;
use App\Models\BhpTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class BhpTypController extends Controller
{
    public function index()
    {
        $bhpTypes = BhpTyp::all();
        return Inertia('BhpTyp/Index', compact('bhpTypes'));
    }

    public function edit(BhpTyp $bhpTyp)
    {
        return Inertia::render('BhpTyp/Edit', [
            'bhp' => [
                'id' => $bhpTyp->id,
                'name' => $bhpTyp->name,
                'deleted_at' => $bhpTyp->deleted_at,
            ],
        ]);
    }

    public function update(BhpTyp $bhpTyp)
    {
        $bhpTyp->update(
            \Illuminate\Support\Facades\Request::validate([
                'name' => ['required', 'max:100'],
            ])
        );
        return Redirect::route('bhpTyp')->with('success', 'Poprawiono.');
    }

    public function destroy(BhpTyp $bhpTyp)
    {
        $bhpTyp->delete();

        // return Redirect::back()->with('success', 'Objekt usunięty.');
        return Redirect::route('bhpTyp')->with('success', 'Usunięto.');
    }

    public function restore(BadaniaTyp $badaniaTyp)
    {
        $badaniaTyp->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }
    public function create()
    {
        return Inertia('BhpTyp/Create');
    }

    public function store(StorePosRequest $req)
    {
        BhpTyp::create($req->validated());
        return Redirect::route('bhpTyp')->with('success', 'Zapisano.');
    }
}
