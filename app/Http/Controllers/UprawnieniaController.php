<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBadaniaRequest;
use App\Http\Requests\StoreUprawnieniaRequest;
use App\Http\Requests\UpdateBhpRequest;
//use App\Models\Bhp;
//use App\Models\BhpTyp;
use App\Enums\TypDokumentu;
use App\Http\Controllers\Concerns\ZapisujeSkan;
use App\Models\Contact;
use App\Models\CtnDocument;
use App\Models\Uprawnienia;
use App\Models\UprawnieniaTyp;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class UprawnieniaController extends Controller
{
    use ZapisujeSkan;

    public function index(Contact $contact, Request $request)
    {
        $dzis = Carbon::today();
        $zKoszem = $request->input('trashed') === 'with';

        $uprawnienias = Uprawnienia::with('skan', 'uprawnieniaTyp')
            ->where('contact_id', $contact->id)
            ->when($zKoszem, fn ($q) => $q->withTrashed())
            // Najświeższy wpis na górze — to on decyduje o ważności.
            ->orderByRaw('`end` IS NULL, `end` DESC')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($uprawnienia) => [
                'id' => $uprawnienia->id,
                'start' => $uprawnienia->start,
                'uprawnienia' => $uprawnienia->uprawnieniaTyp ? $uprawnienia->uprawnieniaTyp : null,
                'end' => $uprawnienia->end,
                'deleted_at' => $uprawnienia->deleted_at,
                'dni' => $uprawnienia->end
                    ? (int) $dzis->diffInDays(Carbon::parse($uprawnienia->end)->startOfDay(), false)
                    : null,
                    'skan' => optional($uprawnienia->skan)->id,
            ]);

        return Inertia::render('Uprawnienia/Index', [
            'filters' => $request->only('search', 'trashed'),
            'pracownik' => trim($contact->last_name.' '.$contact->first_name),
            'contact' => $contact,
            'uprawnienias' => $uprawnienias,
            'userOwner' => Auth::user()->owner,
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', $contact->id)
                ->where('dokumentytyp_id', '3')
                ->when($zKoszem, fn ($q) => $q->withTrashed())
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
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

    public function update(StoreUprawnieniaRequest $req, Contact $contact, Uprawnienia $uprawnienia)
    {
        $uprawnienia->update([
            'uprawnieniaTyp_id' => $req->uprawnieniaTyp_id,
            'start' => $req->start,
            'end' => $req->end,
        ]);

        return Redirect::route('uprawnienia.index', $contact->id)->with('success', 'Element poprawiony.');
    }

    public function create(Contact $contact)
    {
        $contact_id = $contact->id;
        $uprawnieniaTyps = UprawnieniaTyp::all();
        return Inertia('Uprawnienia/Create', compact('contact_id', 'uprawnieniaTyps') + [
            'pracownik' => $this->danePracownika($contact),
        ]);
    }

    public function store(StoreUprawnieniaRequest $req, $contact_id)
    {
        $data = new Uprawnienia;
        $data->uprawnieniaTyp_id=$req->uprawnieniaTyp_id;
        $data->start=$req->start;
        $data->end=$req->end;
        $data->contact_id=$contact_id;
        $data->save();

        $this->zapiszSkan($req, $data, TypDokumentu::UPRAWNIENIA);

        return Redirect::route('uprawnienia.index', $contact_id)->with('success', 'Zapisano.');
    }

    public function destroy(Uprawnienia $uprawnienia)
    {
        $contact_id = $uprawnienia->contact_id;
        $uprawnienia->delete();

        return Redirect::route('uprawnienia.index', $contact_id)->with('success', 'Uprawnienie przeniesione do kosza.');
    }

    public function restore(Uprawnienia $uprawnienia)
    {
        $uprawnienia->restore();

        return Redirect::back()->with('success', 'Uprawnienie przywrócone.');
    }
}
