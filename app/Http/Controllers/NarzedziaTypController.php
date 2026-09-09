<?php

namespace App\Http\Controllers;

use App\Models\GrupaSprzetu;
use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class NarzedziaTypController extends Controller
{
    public function index()
    {
        // Grupa najpierw — w magazynie typy i tak wiszą pod nią.
        $narzedziaTyp = NarzedziaTyp::query()
            ->leftJoin('grupy_sprzetu', 'grupy_sprzetu.id', '=', 'narzedzia_typs.grupa_id')
            ->orderBy('grupy_sprzetu.nazwa')
            ->orderBy('narzedzia_typs.name')
            ->select('narzedzia_typs.*')
            ->with('grupa')
            ->get()
            ->map(fn (NarzedziaTyp $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'grupa' => $t->nazwaGrupy(),
                'deleted_at' => $t->deleted_at,
            ]);

        return Inertia('NarzedziaTyp/Index', [
            'narzedziaTyp' => $narzedziaTyp,
        ]);
    }

    public function edit(NarzedziaTyp $narzedziaTyp)
    {
        return Inertia::render('NarzedziaTyp/Edit', [
            'narzedziaTyp' => [
                'id' => $narzedziaTyp->id,
                'name' => $narzedziaTyp->name,
                'grupa' => $narzedziaTyp->nazwaGrupy(),
                'deleted_at' => $narzedziaTyp->deleted_at,
            ],
            'grupy' => GrupaSprzetu::nazwy(),
        ]);
    }

    public function update(NarzedziaTyp $narzedziaTyp)
    {
        $dane = \Illuminate\Support\Facades\Request::validate([
            'name' => ['required', 'max:100'],
            'grupa' => ['nullable', 'max:100'],
        ]);

        $narzedziaTyp->update([
            'name' => $dane['name'],
            'grupa_id' => optional(GrupaSprzetu::zNazwy($dane['grupa'] ?? null))->id,
        ]);

        return Redirect::route('narzedziaTyp')->with('success', 'Poprawiono.');
    }

    public function destroy(NarzedziaTyp $narzedziaTyp)
    {
        // Na narzedzias.narzedzia_typ_id nie ma klucza obcego, a typ nie ma
        // kosza — baza nie zatrzyma usunięcia używanej nazwy. Sprzęt zostałby
        // z numerem nieistniejącego typu: wypadłby ze swojej grupy w magazynie
        // i pokazywał się osobno. Sprawdzamy wcześniej i mówimy, co stoi na
        // przeszkodzie, tak samo jak przy stanowiskach.
        $sztuk = Narzedzia::where('narzedzia_typ_id', $narzedziaTyp->id)->count();

        if ($sztuk > 0) {
            return Redirect::route('narzedziaTyp')->with(
                'error',
                'Nazwa „'.$narzedziaTyp->name.'" jest przypisana do '.$sztuk.' '
                .($sztuk === 1 ? 'sztuki sprzętu' : 'sztuk sprzętu')
                .' — nie można jej usunąć. Najpierw przepisz ten sprzęt na inną nazwę '
                .'(Sprzęt → wybierz sztukę → Nazwa sprzętu).'
            );
        }

        $narzedziaTyp->delete();

        return Redirect::route('narzedziaTyp')->with('success', 'Usunięto.');
    }

    public function restore(NarzedziaTyp $narzedziaTyp)
    {
        $narzedziaTyp->restore();

        return Redirect::back()->with('success', 'Objekt przywrócony.');
    }

    public function create()
    {
        return Inertia('NarzedziaTyp/Create', [
            'grupy' => GrupaSprzetu::nazwy(),
        ]);
    }

    public function store()
    {
        // Własna walidacja: słownik typów nie ma nic wspólnego z formularzem
        // sprzętu, a wspólny StoreNarzedziaRequest wymagał tu wskazania typu
        // i przez to nie dawał założyć żadnego.
        $dane = \Illuminate\Support\Facades\Request::validate([
            'name' => ['required', 'max:100'],
            'grupa' => ['nullable', 'max:100'],
        ], [
            'name.required' => 'Podaj nazwę typu.',
        ]);

        NarzedziaTyp::create([
            'name' => $dane['name'],
            'grupa_id' => optional(GrupaSprzetu::zNazwy($dane['grupa'] ?? null))->id,
        ]);

        return Redirect::route('narzedziaTyp')->with('success', 'Zapisano.');
    }
}
