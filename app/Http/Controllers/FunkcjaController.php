<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Funkcja;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use App\Http\Requests\StorePosRequest;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FunkcjaController extends Controller
{
    public function index()
    {

        $funkcjas = Funkcja::orderBy('name')->get();

        return Inertia('Funkcja/Index', compact('funkcjas'));

    }

    public function edit(Funkcja $funkcja)
    {
        return Inertia::render('Funkcja/Edit', [
            'funkcja' => [
                'id' => $funkcja->id,
                'name' => $funkcja->name,
                'kierownictwo' => (bool) $funkcja->kierownictwo,
                'deleted_at' => $funkcja->deleted_at,
            ],
        ]);
    }

    public function destroy(Funkcja $funkcja)
    {
        // Stanowisko trzyma się kartotek pracowników kluczem obcym, więc
        // usunięcie używanego kończyło się błędem bazy. Sprawdzamy wcześniej
        // i mówimy, co stoi na przeszkodzie.
        $czynni = Contact::where('funkcja_id', $funkcja->id)->count();
        $wArchiwum = Contact::onlyTrashed()->where('funkcja_id', $funkcja->id)->count();

        if ($czynni + $wArchiwum > 0) {
            return Redirect::route('funkcja')->with('error', $this->komunikatOUzyciu($funkcja, $czynni, $wArchiwum));
        }

        $funkcja->delete();

        return Redirect::route('funkcja')->with('success', 'Stanowisko usunięte.');
    }

    /**
     * Osobno czynni i ci z archiwum — stanowisko potrafi wyglądać na puste,
     * a i tak nie da się go usunąć przez jedną zarchiwizowaną kartotekę.
     */
    private function komunikatOUzyciu(Funkcja $funkcja, int $czynni, int $wArchiwum): string
    {
        $czesci = [];

        if ($czynni > 0) {
            $czesci[] = $czynni.' '.$this->odmien($czynni, 'pracownika', 'pracowników');
        }

        if ($wArchiwum > 0) {
            $czesci[] = $wArchiwum.' '.$this->odmien($wArchiwum, 'pracownika', 'pracowników').' w archiwum';
        }

        return 'Stanowisko „'.$funkcja->name.'" jest przypisane do '.implode(' i ', $czesci)
            .' — nie można go usunąć. Najpierw zmień im stanowisko.';
    }

    private function odmien(int $ile, string $pojedynczo, string $mnogo): string
    {
        return $ile === 1 ? $pojedynczo : $mnogo;
    }


    public function update(Funkcja $funkcja)
    {
        $funkcja->update(
            Request::validate([
                'name' => ['required', 'max:50'],
                'kierownictwo' => ['boolean'],
            ])
        );

        // return Redirect::back()->with('success', 'Poprawiono.');
        return Redirect::route('funkcja')->with('success', 'Poprawiono.');

    }

    public function create()
    {
        return Inertia('Funkcja/Create');
    }

    public function store(StorePosRequest $req)
    {
        Funkcja::create($req->validated());
        return Redirect::route('funkcja')->with('success', 'Zapisano.');
    }
}
