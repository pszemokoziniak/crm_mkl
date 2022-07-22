<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKlientRequest;
use App\Models\Account;
use App\Models\Klient;
use App\Models\KrajTyp;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class KlientController extends Controller
{
    public function index(Organization $organization)
    {
        $budId = $organization->id;
        $klients = Klient::all();

        return Inertia('Klients/Index', compact('klients', 'budId'));

    }
    // public function create()
    // {

    //     return Inertia::render('Contacts/Create', [
    //         'organizations' => Auth::user()->account
    //             ->organizations()
    //             ->orderBy('name')
    //             ->get()
    //             ->map
    //             ->only('id', 'name'),
    //         'accounts' => Auth::user()->account
    //             ->accounts()
    //             ->map
    //             ->only('id', 'name'),
    //     ]);
    // }

    public function edit(Account $account)
    {
        return Inertia::render('Positions/Edit', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'deleted_at' => $account->deleted_at,
            ],
        ]);
    }
    public function destroy(Account $account)
    {

        $account->delete();

        // return Redirect::back()->with('success', 'Objekt usunięty.');
        return Redirect::route('position')->with('success', 'Usunięto.');


    }

    public function restore(Account $account)
    {
        $account->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }

    public function update(Account $account)
    {
        $account->update(
            Request::validate([
                'name' => ['required', 'max:50'],
            ])
        );

        // return Redirect::back()->with('success', 'Poprawiono.');
        return Redirect::route('position')->with('success', 'Poprawiono.');

    }

    public function create(Organization $organization)
    {
        $budId = $organization->id;
        $krajTyps = KrajTyp::all();
        return Inertia('Klients/Create', compact('krajTyps', 'budId'));
    }

    public function store(StoreKlientRequest $req)
    {
        Klient::create($req->validated());
        return Redirect::route('klient.index', $req->organization_id )->with('success', 'Zapisano.');
    }
}
