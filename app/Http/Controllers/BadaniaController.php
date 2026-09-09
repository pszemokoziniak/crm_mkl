<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreBadaniaRequest;
use App\Models\Badania;
use App\Models\BadaniaTyp;
use App\Enums\TypDokumentu;
use App\Http\Controllers\Concerns\ZapisujeSkan;
use App\Models\Contact;
use App\Models\Account;
use App\Models\CtnDocument;
use App\Models\Funkcja;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BadaniaController extends Controller
{
    use ZapisujeSkan;

    public function index(Contact $contact)
    {
        $dzis = Carbon::today();
        $zKoszem = Request::input('trashed') === 'with';

        $bads = Badania::with('skan', 'badaniaTyp')
                ->where('contact_id', $contact->id)
                ->when($zKoszem, fn ($q) => $q->withTrashed())
                // Najświeższe badanie na górze — to ono decyduje, czy człowiek
                // może być na budowie. Bez daty końca na sam dół.
                ->orderByRaw('`end` IS NULL, `end` DESC')
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($badania) => [
                    'id' => $badania->id,
                    'start' => $badania->start,
                    'name' => $badania->badaniaTyp ? $badania->badaniaTyp : null,
                    'end' => $badania->end,
                    'deleted_at' => $badania->deleted_at,
                    'dni' => $badania->end
                        ? (int) $dzis->diffInDays(Carbon::parse($badania->end)->startOfDay(), false)
                        : null,
                    'skan' => optional($badania->skan)->id,
                ]);


        return Inertia::render('Badania/Index', [
            'filters' => Request::all('search', 'trashed'),
            'pracownik' => $this->danePracownika($contact),
            'contact' => $contact,
            'bads' => $bads,
            'userOwner' => Auth::user()->owner,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '1')
                ->when($zKoszem, fn ($q) => $q->withTrashed())
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
        ]);
    }
    public function edit(Contact $contact, Badania $badania)
    {
        $this->wpisPracownika($contact, $badania);

        return Inertia::render('Badania/Edit', [
            // Nagłówek podstrony ma pokazywać, czyją kartę widzimy.
            'pracownik' => $this->danePracownika($contact),
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
        $this->wpisPracownika($contact, $badania);

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
        return Inertia('Badania/Create', compact('contact_id', 'badanias') + [
            'pracownik' => $this->danePracownika($contact),
        ]);
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

        $this->zapiszSkan($req, $data, TypDokumentu::BADANIA);

        return Redirect::route('badania.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(Badania $badania)
    {
        $contact_id = $badania->contact_id;
        $badania->delete();

        return Redirect::route('badania.index', $contact_id)->with('success', 'Badanie przeniesione do kosza.');
    }

    public function restore(Badania $badania)
    {
        $badania->restore();

        return Redirect::back()->with('success', 'Badanie przywrócone.');
    }

}
