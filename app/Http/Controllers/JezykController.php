<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJezykRequest;
use App\Models\Contact;
use App\Models\Jezyk;
use App\Models\JezykTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class JezykController extends Controller
{
    public function index(Contact $contact)
    {
        $jezyks = Jezyk::with('jezykTyp')
            ->where('contact_id', $contact->id)
            ->orderByName()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($jezyk) => [
                'id' => $jezyk->id,
                'poziom' => $jezyk->poziom,
                'jezyk' => $jezyk->jezykTyp ? $jezyk->jezykTyp : null,
            ]);

        return Inertia::render('Jezyk/Index', [
//            'filters' => \Illuminate\Support\Facades\Request::all('search', 'trashed'),
            'contact' => $contact,
            'jezyks' => $jezyks
        ]);
    }
    public function edit(Contact $contact, Jezyk $jezyk)
    {
        return Inertia::render('Jezyk/Edit', [
            'jezyk' => [
                'id' => $jezyk->id,
                'jezykTyp_id' => $jezyk->jezykTyp_id,
                'poziom' => $jezyk->poziom,
                'deleted_at' => $jezyk->deleted_at,
            ],
            'jezykTyps' => JezykTyp::all(),
            'contact' => $contact
        ]);
    }

    public function update(Request $req, Contact $contact, Jezyk $jezyk)
    {
        $data = Jezyk::find($jezyk->id);
        $data->jezykTyp_id = $req->jezykTyp_id;
        $data->poziom = $req->poziom;
        $data->save();

        return Redirect::back()->with('success', 'Element poprawiony.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        $jezykTyps = JezykTyp::all();
        return Inertia('Jezyk/Create', compact('contact_id', 'jezykTyps'));
    }

    public function store(StoreJezykRequest $req, $contact_id)
    {
        $data = new Jezyk;
        $data->jezykTyp_id=$req->jezykTyp_id;
        $data->poziom=$req->poziom;
        $data->contact_id=$contact_id;
        $data->save();
        return Redirect::route('jezyk.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(Jezyk $jezyk)
    {
        $contact_id = $jezyk->contact_id;
        $jezyk->delete();

        return Redirect::route('jezyk.index', $contact_id)->with('success', 'Element usunięty.');
    }

    public function restore(Jezyk $jezyk)
    {
        $jezyk->restore();

        return Redirect::back()->with('success', 'Pracownik przywrócony.');
    }
}
