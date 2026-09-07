<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreA1Request;
use App\Models\A1;
use App\Enums\TypDokumentu;
use App\Http\Controllers\Concerns\ZapisujeSkan;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\KrajTyp;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class A1Controller extends Controller
{
    use ZapisujeSkan;

    public function index(Contact $contact, Request $request)
    {

        $dzis = Carbon::today();
        $zKoszem = $request->input('trashed') === 'with';

        return Inertia::render('A1/Index', [
            'filters' => $request->only('search', 'trashed'),
            'pracownik' => trim($contact->last_name.' '.$contact->first_name),
            'a1s' => A1::with('skan', 'kraj')
                ->where('contact_id', $contact->id)
                ->when($zKoszem, fn ($q) => $q->withTrashed())
                // Najświeższy wpis na górze — to on mówi, czy papier jest ważny.
                ->orderByRaw('`end` IS NULL, `end` DESC')
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($a1) => [
                    'id' => $a1->id,
                    'start' => $a1->start,
                    'end' => $a1->end,
                    'kraj' => $a1->kraj ? $a1->kraj : null,
                    'deleted_at' => $a1->deleted_at,
                    'dni' => $a1->end
                        ? (int) $dzis->diffInDays(Carbon::parse($a1->end)->startOfDay(), false)
                        : null,
                    'skan' => optional($a1->skan)->id,
                ]),
            'contact' => $contact,
            'userOwner' => Auth::user()->owner,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '4')
                ->when($zKoszem, fn ($q) => $q->withTrashed())
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
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
            'countries' => KrajTyp::orderByName()->get(),
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
        $countries = KrajTyp::orderByName()->get();
//        $a1s   = A1::all();
        return Inertia('A1/Create', compact('contact_id', 'countries') + [
            'pracownik' => $this->danePracownika($contact),
        ]);
    }

    public function store(StoreA1Request $req, $contact_id)
    {
        $data = new A1;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->kraj_typs_id=$req->kraj_typs_id;
        $data->contact_id=$contact_id;
        $data->save();

        $this->zapiszSkan($req, $data, TypDokumentu::A1);

        return Redirect::route('a1.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(A1 $a1)
    {
        $contact_id = $a1->contact_id;
        $a1->delete();

        return Redirect::route('a1.index', $contact_id)->with('success', 'Wpis A1 przeniesiony do kosza.');
    }

    /** Trasa a1.restore istniała od dawna, ale kontroler nie miał tej metody. */
    public function restore(A1 $a1)
    {
        $a1->restore();

        return Redirect::back()->with('success', 'Wpis A1 przywrócony.');
    }
}
