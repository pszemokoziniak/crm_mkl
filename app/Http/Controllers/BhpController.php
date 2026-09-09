<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBhpRequest;
use App\Http\Requests\UpdateBhpRequest;
use App\Models\Bhp;
use App\Models\BhpTyp;
use App\Enums\TypDokumentu;
use App\Http\Controllers\Concerns\ZapisujeSkan;
use App\Models\Contact;
use App\Models\CtnDocument;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class BhpController extends Controller
{
    use ZapisujeSkan;

    public function index(Contact $contact, Request $request)
    {
        $dzis = Carbon::today();
        $zKoszem = $request->input('trashed') === 'with';

        $bhps = Bhp::with('skan', 'bhpTyp')
            ->where('contact_id', $contact->id)
            ->when($zKoszem, fn ($q) => $q->withTrashed())
            // Najświeższy wpis na górze — to on decyduje o ważności.
            ->orderByRaw('`end` IS NULL, `end` DESC')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($bhp) => [
                'id' => $bhp->id,
                'start' => $bhp->start,
                'bhp' => $bhp->bhpTyp ? $bhp->bhpTyp : null,
                'end' => $bhp->end,
                'deleted_at' => $bhp->deleted_at,
                'dni' => $bhp->end
                    ? (int) $dzis->diffInDays(Carbon::parse($bhp->end)->startOfDay(), false)
                    : null,
                    'skan' => optional($bhp->skan)->id,
            ]);

        return Inertia::render('Bhp/Index', [
            'filters' => $request->only('search', 'trashed'),
            'pracownik' => $this->danePracownika($contact),
            'contact' => $contact,
            'bhps' => $bhps,
            'userOwner' => Auth::user()->owner,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '2')
                ->when($zKoszem, fn ($q) => $q->withTrashed())
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
        ]);
    }
    public function edit(Contact $contact, Bhp $bhp)
    {
        $this->wpisPracownika($contact, $bhp);

        return Inertia::render('Bhp/Edit', [
            // Nagłówek podstrony ma pokazywać, czyją kartę widzimy.
            'pracownik' => $this->danePracownika($contact),
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
        $this->wpisPracownika($contact, $bhp);

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
        return Inertia('Bhp/Create', compact('contact_id', 'bhpTyps') + [
            'pracownik' => $this->danePracownika($contact),
        ]);
    }

    public function store(StoreBhpRequest $req, $contact_id)
    {
        $data = new Bhp;
        $data->bhpTyp_id = $req->bhpTyp_id;
        $data->start = $req->start;
        $data->end = $req->end;
        $data->contact_id = $contact_id;
        $data->save();

        $this->zapiszSkan($req, $data, TypDokumentu::BHP);

        return Redirect::route('bhp.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(Bhp $bhp)
    {
        $contact_id = $bhp->contact_id;
        $bhp->delete();

        return Redirect::route('bhp.index', $contact_id)->with('success', 'Szkolenie BHP przeniesione do kosza.');
    }

    public function restore(Bhp $bhp)
    {
        $bhp->restore();

        return Redirect::back()->with('success', 'Szkolenie BHP przywrócone.');
    }
}
