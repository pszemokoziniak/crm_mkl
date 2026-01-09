<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBhpRequest;
use App\Http\Requests\UpdateBhpRequest;
use App\Models\Bhp;
use App\Models\BhpTyp;
use App\Models\Contact;
use App\Models\CtnDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class BhpController extends Controller
{
    public function index(Contact $contact)
    {
        $bhps = Bhp::with('bhpTyp')
            ->where('contact_id', $contact->id)
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
            'bhps' => $bhps,
            'userOwner' => Auth::user()->owner,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '2')
                ->paginate(10)
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

    public function update(UpdateBhpRequest $req, Contact $contact, Bhp $bhp)
    {
        $bhp->update([
            'bhpTyp_id' => $req->bhpTyp_id,
            'start' => $req->start,
            'end' => $req->end,
        ]);

        return Redirect::route('bhp.index', $contact->id)->with('success', 'Element poprawiony.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        $bhpTyps = BhpTyp::all();
        return Inertia('Bhp/Create', compact('contact_id', 'bhpTyps'));
    }

    public function store(StoreBhpRequest $req, $contact_id)
    {
        $data = new Bhp;
        $data->bhpTyp_id = $req->bhpTyp_id;
        $data->start = $req->start;
        $data->end = $req->end;
        $data->contact_id = $contact_id;
        $data->save();

        return Redirect::route('bhp.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(Bhp $bhp)
    {
        $contact_id = $bhp->contact_id;
        $bhp->delete();

        return Redirect::route('bhp.index', $contact_id)->with('success', 'Element usunięty.');
    }

    public function restore(Bhp $bhp)
    {
        $bhp->restore();

        return Redirect::back()->with('success', 'Element przywrócony.');
    }
}
