<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreA1Request;
use App\Models\A1;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\KrajTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class A1Controller extends Controller
{
    public function index(Contact $contact)
    {

        return Inertia::render('A1/Index', [
            'a1s' => A1::with('kraj')
                ->where('contact_id', $contact->id)
                ->get()
                ->map(fn ($a1) => [
                    'id' => $a1->id,
                    'start' => $a1->start,
                    'end' => $a1->end,
                    'kraj' => $a1->kraj ? $a1->kraj : null,
                ]),
            'contact' => $contact,
            'userOwner' => Auth::user()->owner,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '4')
                ->paginate(10)
        ]);
    }
    public function edit(Contact $contact, A1 $a1)
    {
        return Inertia::render('A1/Edit', [
            'a1' => [
                'id' => $a1->id,
                'start' => $a1->start,
                'kraj_typs_id' => $a1->kraj_typs_id,
                'end' => $a1->end,
            ],
            'contact' => $contact,
            'countries' => KrajTyp::get(),
        ]);
    }

    public function update(StoreA1Request $req)
    {
        $data = A1::find($req->id);
        $data->start = $req->start;
        $data->end = $req->end;
        $data->kraj_typs_id = $req->kraj_typs_id;
        $data->save();

        return Redirect::back()->with('success', 'Element poprawiony.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        $countries = KrajTyp::get();
//        $a1s   = A1::all();
        return Inertia('A1/Create', compact('contact_id', 'countries'));
    }

    public function store(StoreA1Request $req, $contact_id)
    {
        $data = new A1;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->kraj_typs_id=$req->kraj_typs_id;
        $data->contact_id=$contact_id;
        $data->save();
        return Redirect::route('a1.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(A1 $a1)
    {
        $contact_id = $a1->contact_id;
        $a1->delete();

        return Redirect::route('a1.index', $contact_id)->with('success', 'Element usunięty.');
    }
}
