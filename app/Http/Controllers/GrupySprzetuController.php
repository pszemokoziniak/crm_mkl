<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GrupaSprzetu;
use App\Models\NarzedziaTyp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Grupy sprzętu (Kontener, Manitou, JLG…) — zakładanie, nazwa, usuwanie
 * i przypisywanie do nich modeli sprzętu.
 *
 * Grupa jest osobnym rekordem, więc może być pusta: magazyn zakłada ją
 * z góry i dopiero potem wrzuca do niej sprzęt.
 */
class GrupySprzetuController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('GrupySprzetu/Index', [
            'grupy' => $this->grupy(),
            'bezGrupy' => $this->modele(null),
        ]);
    }

    /** Nowa, jeszcze pusta grupa — sprzęt dorzuca się do niej później. */
    public function store(Request $request): RedirectResponse
    {
        $nazwa = trim((string) $request->validate([
            'nazwa' => ['required', 'string', 'max:100'],
        ])['nazwa']);

        if ($istniejaca = $this->poNazwie($nazwa)) {
            return Redirect::route('grupySprzetu')
                ->with('error', "Grupa „{$istniejaca->nazwa}” już istnieje.");
        }

        GrupaSprzetu::create(['nazwa' => $nazwa]);

        return Redirect::route('grupySprzetu')
            ->with('success', "Grupa „{$nazwa}” utworzona. Przypisz do niej sprzęt poniżej.");
    }

    public function update(Request $request, GrupaSprzetu $grupa): RedirectResponse
    {
        $nowa = trim((string) $request->validate([
            'nazwa' => ['required', 'string', 'max:100'],
        ])['nazwa']);

        $stara = $grupa->nazwa;

        if ($stara === $nowa) {
            return Redirect::route('grupySprzetu')->with('success', 'Nazwa bez zmian.');
        }

        // Nazwa zajęta = połączenie dwóch grup w jedną. To bywa zamierzone
        // (scalanie literówki), ale użytkownik musi wiedzieć, co się stało.
        $cel = $this->poNazwie($nowa);

        if ($cel && $cel->id !== $grupa->id) {
            $ile = $grupa->typy()->count();
            $grupa->typy()->update(['grupa_id' => $cel->id]);
            $grupa->delete();

            return Redirect::route('grupySprzetu')->with(
                'success',
                "Grupy połączone: „{$stara}” przeniesiona do „{$cel->nazwa}” ({$ile} modeli)."
            );
        }

        $grupa->update(['nazwa' => $nowa]);

        return Redirect::route('grupySprzetu')
            ->with('success', "Grupa „{$stara}” nazywa się teraz „{$nowa}”.");
    }

    /**
     * Usunięcie grupy nie kasuje sprzętu — modele zostają, tracą tylko
     * przypisanie i lądują w magazynie jako osobne pozycje.
     */
    public function destroy(GrupaSprzetu $grupa): RedirectResponse
    {
        $nazwa = $grupa->nazwa;
        $ile = $grupa->typy()->count();

        // Klucz obcy jest na nullOnDelete, ale zerujemy jawnie — czytelniej
        // niż liczyć na zachowanie bazy przy odczycie kodu.
        $grupa->typy()->update(['grupa_id' => null]);
        $grupa->delete();

        return Redirect::route('grupySprzetu')->with(
            'success',
            $ile > 0
                ? "Grupa „{$nazwa}” usunięta. {$ile} modeli zostało bez grupy — sprzęt nietknięty."
                : "Pusta grupa „{$nazwa}” usunięta."
        );
    }

    /** Przeniesienie wskazanych modeli do grupy (albo poza grupy). */
    public function przypisz(Request $request): RedirectResponse
    {
        $dane = $request->validate([
            'modele' => ['required', 'array', 'min:1'],
            'modele.*' => ['integer'],
            'grupa_id' => ['nullable', 'integer', 'exists:grupy_sprzetu,id'],
        ]);

        $grupa = $dane['grupa_id'] ? GrupaSprzetu::find($dane['grupa_id']) : null;
        $ile = NarzedziaTyp::whereIn('id', $dane['modele'])
            ->update(['grupa_id' => $grupa?->id]);

        return Redirect::route('grupySprzetu')->with(
            'success',
            $grupa
                ? "Przeniesiono {$ile} modeli do grupy „{$grupa->nazwa}”."
                : "Usunięto przypisanie do grupy dla {$ile} modeli."
        );
    }

    /**
     * Nazwy porównujemy bez względu na wielkość liter i spacje na brzegach —
     * inaczej "manitou" zakładałoby drugą grupę obok "Manitou".
     */
    private function poNazwie(string $nazwa): ?GrupaSprzetu
    {
        return GrupaSprzetu::whereRaw('LOWER(TRIM(nazwa)) = ?', [mb_strtolower(trim($nazwa))])->first();
    }

    /**
     * Grupy z liczbą modeli i sztuk — bez liczby sztuk nie wiadomo,
     * czego dotyczy usunięcie grupy.
     *
     * @return array<int, array<string, mixed>>
     */
    private function grupy(): array
    {
        return GrupaSprzetu::query()
            ->withCount('typy as modeli')
            ->orderBy('nazwa')
            ->get()
            ->map(fn (GrupaSprzetu $g) => [
                'id' => $g->id,
                'nazwa' => $g->nazwa,
                'modeli' => (int) $g->modeli,
                'modele' => $this->modele($g->id),
            ])
            ->map(fn (array $g) => $g + ['sztuk' => array_sum(array_column($g['modele'], 'sztuk'))])
            ->all();
    }

    /** @return array<int, array{id: int, name: string, sztuk: int}> */
    private function modele(?int $grupaId): array
    {
        return NarzedziaTyp::query()
            ->when($grupaId === null,
                fn ($q) => $q->whereNull('grupa_id'),
                fn ($q) => $q->where('grupa_id', $grupaId))
            ->orderBy('name')
            ->withCount(['narzedzias as sztuk'])
            ->get(['id', 'name'])
            ->map(fn (NarzedziaTyp $t) => ['id' => $t->id, 'name' => $t->name, 'sztuk' => (int) $t->sztuk])
            ->all();
    }
}
