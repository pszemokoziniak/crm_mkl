<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreA1Request;
use App\Models\A1;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class A1Controller extends Controller
{
    public function index(Contact $contact, A1 $a1)
    {
        $a1s = A1::where('contact_id', $contact->id)
            ->orderByName()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($a1) => [
                'id' => $a1->id,
                'start' => $a1->start,
                'end' => $a1->end,
            ]);

        return Inertia::render('A1/Index', [
            'a1s' => $a1s,
            'contact' => $contact
        ]);
    }
    public function edit(Contact $contact, A1 $a1)
    {
        return Inertia::render('A1/Edit', [
            'a1' => [
                'id' => $a1->id,
                'start' => $a1->start,
                'end' => $a1->end,
            ],
            'contact' => $contact
        ]);
    }

    public function update(Request $req)
    {
        $data = A1::find($req->id);
        $data->start = $req->start;
        $data->end = $req->end;
        $data->save();

        return Redirect::back()->with('success', 'Element poprawiony.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        $a1s   = A1::all();
        return Inertia('A1/Create', compact('contact_id', 'a1s'));
    }

    public function store(StoreA1Request $req, $contact_id)
    {
        $data = new A1;
        $data->start=$req->start;
        $data->end=$req->end;
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

//    public function restore(Jezyk $jezyk)
//    {
//        $jezyk->restore();
//
//        return Redirect::back()->with('success', 'Pracownik przywrócony.');
//    }
}
