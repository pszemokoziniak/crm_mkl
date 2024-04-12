<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePbiozRequest;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\Pbioz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PbiozController extends Controller
{
    public function index(Contact $contact)
    {
        $pbioz = Pbioz::where('contact_id', $contact->id)
            ->orderByName()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($pbioz) => [
                'id' => $pbioz->id,
                'name' => $pbioz->name,
                'start' => $pbioz->start,
                'end' => $pbioz->end,
            ]);

        return Inertia::render('Pbioz/Index', [
            'filters' => \Illuminate\Support\Facades\Request::all('search', 'trashed'),
            'contact' => $contact,
            'pbioz' => $pbioz,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '5')
                ->paginate(10)
        ]);
    }
    public function edit(Contact $contact, Pbioz $pbioz)
    {
        return Inertia::render('Pbioz/Edit', [
            'pbioz' => [
                'id' =>$pbioz->id,
                'name' => $pbioz->name,
                'start' => $pbioz->start,
                'end' => $pbioz->end,
                'deleted_at' => $pbioz->deleted_at,
            ],
            'contact' => $contact
        ]);
    }

    public function update(StorePbiozRequest $req, Contact $contact, Pbioz $pbioz)
    {
        $data = Pbioz::find($pbioz->id);
        $data->name = $req->name;
        $data->start = $req->start;
        $data->end = $req->end;
        $data->save();

        return Redirect::back()->with('success', 'Element poprawiony.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        return Inertia('Pbioz/Create', compact('contact_id'));
    }

    public function store(StorePbiozRequest $req, $contact_id)
    {
        $data = new Pbioz;
        $data->name=$req->name;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->contact_id=$contact_id;
        $data->save();
        return Redirect::route('pbioz.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(Pbioz $pbioz)
    {
        $contact_id = $pbioz->contact_id;
        $pbioz->delete();

        return Redirect::route('pbioz.index', $contact_id)->with('success', 'Pracownik usunięty.');
    }

    public function restore(Pbioz $pbioz)
    {
        $pbioz->restore();

        return Redirect::back()->with('success', 'Pracownik przywrócony.');
    }
}
