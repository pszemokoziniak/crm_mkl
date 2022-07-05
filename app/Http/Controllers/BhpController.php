<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBadaniaRequest;
//use App\Models\Badania;
//use App\Models\BadaniaTyp;
use App\Models\Bhp;
use App\Models\BhpTyp;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class BhpController extends Controller
{
    public function index(Contact $contact)
    {
        $bhps = Bhp::with('bhpTyp')
            ->orderByName()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($bhp) => [
                'id' => $bhp->id,
                'start' => $bhp->start,
                'bhp' => $bhp->bhpTyp ? $bhp->bhpTyp : null,
                'end' => $bhp->end,
            ]);

        return Inertia::render('Bhp/Index', [
            'filters' => \Illuminate\Support\Facades\Request::all('search', 'trashed'),
            'contact' => $contact,
            'bhps' => $bhps
        ]);
    }
    public function edit(Contact $contact, Bhp $bhp)
    {
        return Inertia::render('Bhp/Edit', [
            'bhp' => [
                'id' => $bhp->id,
                'bhpTyp_id' => $bhp->bhpTyp_id,
                'start' => $bhp->start,
                'end' => $bhp->end,
                'deleted_at' => $bhp->deleted_at,
            ],
            'bhpTyps' => BhpTyp::all(),
            'contact' => $contact
        ]);
    }

    public function update(Contact $contact, Bhp $bhp)
    {
//        dd($bhp);
        $bhp->update(
            Request::validate([
                'contact_id' => ['required'],
                'bhpTyp_id' => ['required'],
                'start' => ['required', 'date'],
                'end' => ['required', 'date'],
            ])
        );
        dd($bhp);
        return Redirect::back()->with('success', 'Element poprawiony.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        $bhpTyps = BhpTyp::all();
        return Inertia('Bhp/Create', compact('contact_id', 'bhpTyps'));
    }

    public function store(StoreBadaniaRequest $req, $contact_id)
    {
        // dd($contact_id);
        $data = new Bhp;
        $data->bhpTyp_id=$req->bhpTyp_id;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->contact_id=$contact_id;
        $data->save();
        return Redirect::route('bhp.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(Badania $badania)
    {
        $contact_id = $badania->contact_id;
        $badania->delete();

        return Redirect::route('badania.index', $contact_id)->with('success', 'Pracownik usunięty.');
    }

    public function restore(Badania $badania)
    {
        $contact->restore();

        return Redirect::back()->with('success', 'Pracownik przywrócony.');
    }
}
