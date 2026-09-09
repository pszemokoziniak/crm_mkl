<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePbiozRequest;
use App\Enums\TypDokumentu;
use App\Http\Controllers\Concerns\ZapisujeSkan;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\Pbioz;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PbiozController extends Controller
{
    use ZapisujeSkan;

    public function index(Contact $contact, Request $request)
    {
        $dzis = Carbon::today();
        $zKoszem = $request->input('trashed') === 'with';

        $pbioz = Pbioz::with('skan')->where('contact_id', $contact->id)
            ->when($zKoszem, fn ($q) => $q->withTrashed())
            // Najświeższy wpis na górze — to on decyduje o ważności.
            ->orderByRaw('`end` IS NULL, `end` DESC')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($pbioz) => [
                'id' => $pbioz->id,
                'name' => $pbioz->name,
                'start' => $pbioz->start,
                'end' => $pbioz->end,
                'deleted_at' => $pbioz->deleted_at,
                'dni' => $pbioz->end
                    ? (int) $dzis->diffInDays(Carbon::parse($pbioz->end)->startOfDay(), false)
                    : null,
                    'skan' => optional($pbioz->skan)->id,
            ]);

        return Inertia::render('Pbioz/Index', [
            'filters' => $request->only('search', 'trashed'),
            'pracownik' => $this->danePracownika($contact),
            'contact' => $contact,
            'pbioz' => $pbioz,
            'userOwner' => Auth::user()->owner,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '5')
                ->when($zKoszem, fn ($q) => $q->withTrashed())
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
        ]);
    }
    public function edit(Contact $contact, Pbioz $pbioz)
    {
        $this->wpisPracownika($contact, $pbioz);

        return Inertia::render('Pbioz/Edit', [
            // Nagłówek podstrony ma pokazywać, czyją kartę widzimy.
            'pracownik' => $this->danePracownika($contact),
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
        $this->wpisPracownika($contact, $pbioz);

        $pbioz->update([
            'name' => $req->name,
            'start' => $req->start,
            'end' => $req->end,
        ]);

        return Redirect::back()->with('success', 'Element poprawiony.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        return Inertia('Pbioz/Create', compact('contact_id') + [
            'pracownik' => $this->danePracownika($contact),
        ]);
    }

    public function store(StorePbiozRequest $req, $contact_id)
    {
        $data = new Pbioz;
        $data->name=$req->name;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->contact_id=$contact_id;
        $data->save();

        $this->zapiszSkan($req, $data, TypDokumentu::PBIOZ);

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
