<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKlientRequest;
use App\Models\Account;
use App\Models\Klient;
use App\Models\KrajTyp;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KlientController extends Controller
{
    public function index(Organization $organization)
    {
        $budId = $organization->id;
        $klients = Klient::where('organization_id', $organization->id)->get();

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

    public function edit(Organization $organization, Klient $klient)
    {
        return Inertia::render('Klients/Edit', [
            'klient' => [
                'id' => $klient->id,
                'organization_id' => $klient->organization_id,
                'nameFirma' => $klient->nameFirma,
                'adres' => $klient->adres,
                'city' => $klient->city,
                'country_id' => $klient->country_id,
                'nameKontakt' => $klient->nameKontakt,
                'phone' => $klient->phone,
                'email' => $klient->email,
            ],
            'budId' => $organization->id,
            'krajTyps' => KrajTyp::all(),

        ]);
    }
    public function destroy(Klient $klient)
    {
        $klient->delete();
//        return Redirect::route('position')->with('success', 'Usunięto.');
        return Redirect::route('klient.index', $klient->organization_id)->with('success', 'Usunięto.');


    }

    public function restore(Account $account)
    {
        $account->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }

    public function update(Klient $klient)
    {
        $klient->update(
            \Illuminate\Support\Facades\Request::validate([
                'organization_id' => ['required', 'max:50'],
                'nameFirma' => ['required', 'max:550'],
                'adres' => ['nullable', 'max:1150'],
                'city' => ['nullable', 'max:150'],
                'country_id' => ['nullable', 'max:550'],
                'nameKontakt' => ['nullable', 'max:550'],
                'phone' => ['nullable', 'max:150'],
                'email' => ['nullable', 'max:200', 'email'],
            ])
        );
//        $data = Klient::find($klient->id);
//        $data->organization_id = $klient->organization_id;
//        $data->nameFirma = $klient->nameFirma;
//        $data->adres = $klient->adres;
//        $data->city = $klient->city;
//        $data->country_id = $klient->country_id;
//        $data->nameKontakt = $klient->nameKontakt;
//        $data->phone = $klient->phone;
//        $data->email = $klient->email;
//        $data->save();


        // return Redirect::back()->with('success', 'Poprawiono.');
        return Redirect::route('klient.index', $klient->organization_id)->with('success', 'Poprawiono.');

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
