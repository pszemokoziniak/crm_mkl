<?php

namespace App\Http\Controllers;

use App\Models\Funkcja;
use App\Models\Account;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use App\Http\Requests\StorePosRequest;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FunkcjaController extends Controller
{
    public function index()
    {

        $funkcjas = Funkcja::all();

        return Inertia('Funkcja/Index', compact('funkcjas'));

    }

    public function edit(Funkcja $funkcja)
    {
        return Inertia::render('Funkcja/Edit', [
            'funkcja' => [
                'id' => $funkcja->id,
                'name' => $funkcja->name,
                'deleted_at' => $funkcja->deleted_at,
            ],
        ]);
    }

    public function destroy(Funkcja $funkcja)
    {
        $funkcja->delete();

        // return Redirect::back()->with('success', 'Objekt usunięty.');
        return Redirect::route('funkcja')->with('success', 'Usunięto.');
    }

    public function restore(Account $account)
    {
        $account->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }

    public function update(Funkcja $funkcja)
    {
        $funkcja->update(
            Request::validate([
                'name' => ['required', 'max:50'],
            ])
        );

        // return Redirect::back()->with('success', 'Poprawiono.');
        return Redirect::route('funkcja')->with('success', 'Poprawiono.');

    }

    public function create()
    {
        return Inertia('Funkcja/Create');
    }

    public function store(StorePosRequest $req)
    {
        Funkcja::create($req->validated());
        return Redirect::route('funkcja')->with('success', 'Zapisano.');
    }

}
