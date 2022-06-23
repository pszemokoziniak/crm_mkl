<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreBadaniaRequest;
use App\Models\Badania;
use App\Models\BadaniaTyp;
use App\Models\Contact;
use App\Models\Account;
use App\Models\Funkcja;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BadaniaController extends Controller
{
    public function index(Contact $contact)
    {
//        dd(Badania::join('badania_typs', 'badaniaTyp_id', '=', 'badania_typs.id')
//            ->where('contact_id', $contact->id)->get());
        return Inertia::render('Badania/Index', [
            'badanias' => Badania::join('badania_typs', 'badaniaTyp_id', 'badania_typs.id')
            ->where('contact_id', $contact->id)->get(),
            'contact' => $contact,
        ]);
    }
    public function edit(Contact $contact, Badania $badania)
    {
//        dd($badania);
        return Inertia::render('Badania/Edit', [
            'badanie' => [
                'id' => $badania->id,
                'badaniaTyp_id' => $badania->badaniaTyp_id,
                'start' => $badania->start,
                'end' => $badania->end,
                'deleted_at' => $badania->deleted_at,
            ],
            'badanias' => BadaniaTyp::all(),
            'contact' => $contact
        ]);
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        $badanias = BadaniaTyp::all();
        return Inertia('Badania/Create', compact('contact_id', 'badanias'));
    }

    public function store(StoreBadaniaRequest $req, $contact_id)
    {
        $data = new Badania;
        $data->badaniaTyp_id=$req->badaniaTyp_id;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->contact_id=$contact_id;
        $data->save();
        return Redirect::route('badania.index', $contact_id)->with('success', 'Zapisano.');
    }

}
