<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBadaniaRequest;
use App\Http\Requests\UpdateBhpRequest;
//use App\Models\Bhp;
//use App\Models\BhpTyp;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\Uprawnienia;
use App\Models\UprawnieniaTyp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class UprawnieniaController extends Controller
{
    public function index(Contact $contact)
    {
        $uprawnienias = Uprawnienia::with('uprawnieniaTyp')
            ->where('contact_id', $contact->id)
            ->orderByName()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($uprawnienia) => [
                'id' => $uprawnienia->id,
                'start' => $uprawnienia->start,
                'uprawnienia' => $uprawnienia->uprawnieniaTyp ? $uprawnienia->uprawnieniaTyp : null,
                'end' => $uprawnienia->end,
            ]);

        return Inertia::render('Uprawnienia/Index', [
//            'filters' => \Illuminate\Support\Facades\Request::all('search', 'trashed'),
            'contact' => $contact,
            'uprawnienias' => $uprawnienias,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '3')
                ->paginate(10)
        ]);
    }
    public function edit(Contact $contact, Uprawnienia $uprawnienia)
    {
        return Inertia::render('Uprawnienia/Edit', [
            'uprawnienia' => [
                'id' => $uprawnienia->id,
                'uprawnieniaTyp_id' => $uprawnienia->uprawnieniaTyp_id,
                'start' => $uprawnienia->start,
                'end' => $uprawnienia->end,
                'deleted_at' => $uprawnienia->deleted_at,
            ],
            'uprawnieniaTyps' => UprawnieniaTyp::all(),
            'contact' => $contact
        ]);
    }

    public function update(UpdateBhpRequest $req, Contact $contact, Uprawnienia $uprawnienia)
    {
        $data = Uprawnienia::find($uprawnienia->id);
        $data->uprawnieniaTyp_id = $req->uprawnieniaTyp_id;
        $data->start = $req->start;
        $data->end = $req->end;
        $data->save();

        return Redirect::back()->with('success', 'Element poprawiony.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        $uprawnieniaTyps = UprawnieniaTyp::all();
        return Inertia('Uprawnienia/Create', compact('contact_id', 'uprawnieniaTyps'));
    }

    public function store(StoreBadaniaRequest $req, $contact_id)
    {
        $data = new Uprawnienia;
        $data->uprawnieniaTyp_id=$req->uprawnieniaTyp_id;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->contact_id=$contact_id;
        $data->save();
        return Redirect::route('uprawnienia.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(Uprawnienia $uprawnienia)
    {
        $contact_id = $uprawnienia->contact_id;
        $uprawnienia->delete();

        return Redirect::route('uprawnienia.index', $contact_id)->with('success', 'Element usunięty.');
    }

    public function restore(Uprawnienia $uprawnienia)
    {
        $uprawnienia->restore();

        return Redirect::back()->with('success', 'Element przywrócony.');
    }
}
