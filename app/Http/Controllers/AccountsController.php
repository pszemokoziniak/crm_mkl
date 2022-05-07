<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Account;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use App\Http\Requests\StorePosRequest;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Validation\Rule;
use Inertia\Inertia;



class AccountsController extends Controller
{
    public function index()
    {

        $accounts = Account::all();
        // dd($accounts);

        return Inertia('Positions/Index', compact('accounts'));

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

    public function create()
    {
        return Inertia('Positions/Create');
    }

    public function store(StorePosRequest $req)
    {
        Account::create($req->validated());
        return Redirect::route('position')->with('success', 'Zapisano.');
    }






}
