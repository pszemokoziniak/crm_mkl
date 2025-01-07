<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreBadaniaRequest;
use App\Models\Badania;
use App\Models\BadaniaTyp;
use App\Models\Contact;
use App\Models\Account;
use App\Models\CtnDocument;
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
        $bads = Badania::with('badaniaTyp')
                ->where('contact_id', $contact->id)
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($badania) => [
                    'id' => $badania->id,
                    'start' => $badania->start,
                    'name' => $badania->badaniaTyp ? $badania->badaniaTyp : null,
                    'end' => $badania->end,
                ]);


        return Inertia::render('Badania/Index', [
            'filters' => Request::all('search', 'trashed'),
            'contact' => $contact,
            'bads' => $bads,
            'userOwner' => Auth::user()->owner,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '1')
                ->paginate(10)
        ]);
    }
    public function edit(Contact $contact, Badania $badania)
    {
        return Inertia::render('Badania/Edit', [
            'badanie' => [
                'id' => $badania->id,
                'badaniaTyp_id' => $badania->badaniaTyp_id,
                'start' => $badania->start,
                'end' => $badania->end,
                'deleted_at' => $badania->deleted_at,
            ],
            'badaniaTyps' => BadaniaTyp::all(),
            'contact' => $contact
        ]);
    }

    public function update(Contact $contact, Badania $badania)
    {
        $badania->update(
            Request::validate([
                'badaniaTyp_id' => ['required', 'max:50'],
                'start' => 'required | date | before:end',
                'end' => 'required | date | after:start',
            ])
        );

        return Redirect::back()->with('success', 'Badania poprawione.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        $badanias = BadaniaTyp::all();
        return Inertia('Badania/Create', compact('contact_id', 'badanias'));
    }

    public function store(StoreBadaniaRequest $req, $contact_id)
    {
        // dd($contact_id);
        $data = new Badania;
        $data->badaniaTyp_id=$req->badaniaTyp_id;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->contact_id=$contact_id;
        $data->save();
        return Redirect::route('badania.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(Badania $badania)
    {
        $contact_id = $badania->contact_id;
        $badania->delete();

        return Redirect::route('badania.index', $contact_id)->with('success', 'Pracownik usunięty.');
    }

    public function restore(Badania $badania)
    {
        $badania->restore();

        return Redirect::back()->with('success', 'Pracownik przywrócony.');
    }

}
