<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Narzedzia;
use App\Models\NarzedziaTyp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Grupy sprzętu (Kontener, Manitou, JLG…). To nie osobna tabela, tylko
 * kolumna `kategoria` przy modelu sprzętu — dlatego dotąd nie dało się
 * grupy przemianować ani usunąć, a literówka przy dodawaniu modelu
 * tworzyła nową grupę obok istniejącej.
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

    /** Przemianowanie grupy zmienia kategorię wszystkim jej modelom. */
    public function update(Request $request): RedirectResponse
    {
        $dane = $request->validate([
            'stara' => ['required', 'string', 'max:100'],
            'nowa' => ['required', 'string', 'max:100'],
        ]);

        $stara = trim($dane['stara']);
        $nowa = trim($dane['nowa']);

        if ($stara === $nowa) {
            return Redirect::route('grupySprzetu')->with('success', 'Nazwa bez zmian.');
        }

        $ile = NarzedziaTyp::where('kategoria', $stara)->count();

        if ($ile === 0) {
            return Redirect::route('grupySprzetu')->with('error', 'Nie ma takiej grupy.');
        }

        // Nazwa zajęta = połączenie dwóch grup w jedną. To bywa zamierzone
        // (scalanie literówki), ale użytkownik musi wiedzieć, co się stało.
        $scalenie = NarzedziaTyp::where('kategoria', $nowa)->exists();

        NarzedziaTyp::where('kategoria', $stara)->update(['kategoria' => $nowa]);

        return Redirect::route('grupySprzetu')->with(
            'success',
            $scalenie
                ? "Grupy połączone: „{$stara}” przeniesiona do „{$nowa}” ({$ile} modeli)."
                : "Grupa „{$stara}” nazywa się teraz „{$nowa}” ({$ile} modeli)."
        );
    }

    /**
     * Usunięcie grupy nie kasuje sprzętu — modele zostają, tracą tylko
     * przypisanie i lądują w magazynie jako osobne pozycje.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $nazwa = trim((string) $request->validate([
            'nazwa' => ['required', 'string', 'max:100'],
        ])['nazwa']);

        $ile = NarzedziaTyp::where('kategoria', $nazwa)->count();

        if ($ile === 0) {
            return Redirect::route('grupySprzetu')->with('error', 'Nie ma takiej grupy.');
        }

        NarzedziaTyp::where('kategoria', $nazwa)->update(['kategoria' => null]);

        return Redirect::route('grupySprzetu')
            ->with('success', "Grupa „{$nazwa}” usunięta. {$ile} modeli zostało bez grupy — sprzęt nietknięty.");
    }

    /** Przeniesienie wskazanych modeli do grupy (albo poza grupy). */
    public function przypisz(Request $request): RedirectResponse
    {
        $dane = $request->validate([
            'modele' => ['required', 'array', 'min:1'],
            'modele.*' => ['integer'],
            'grupa' => ['nullable', 'string', 'max:100'],
        ]);

        $grupa = $dane['grupa'] !== null ? trim($dane['grupa']) : null;
        $ile = NarzedziaTyp::whereIn('id', $dane['modele'])->update(['kategoria' => $grupa ?: null]);

        return Redirect::route('grupySprzetu')->with(
            'success',
            $grupa
                ? "Przeniesiono {$ile} modeli do grupy „{$grupa}”."
                : "Usunięto przypisanie do grupy dla {$ile} modeli."
        );
    }

    /**
     * Grupy z liczbą modeli i sztuk — bez liczby sztuk nie wiadomo,
     * co się skasuje przy usuwaniu grupy.
     *
     * @return array<int, array<string, mixed>>
     */
    private function grupy(): array
    {
        return NarzedziaTyp::query()
            ->whereNotNull('kategoria')
            ->where('kategoria', '!=', '')
            ->select('kategoria')
            ->selectRaw('COUNT(*) as modeli')
            ->groupBy('kategoria')
            ->orderBy('kategoria')
            ->get()
            ->map(fn ($w) => [
                'nazwa' => $w->kategoria,
                'modeli' => (int) $w->modeli,
                'sztuk' => (int) Narzedzia::whereIn(
                    'narzedzia_typ_id',
                    NarzedziaTyp::where('kategoria', $w->kategoria)->select('id')
                )->count(),
                'modele' => $this->modele($w->kategoria),
            ])
            ->all();
    }

    /** @return array<int, array{id: int, name: string, sztuk: int}> */
    private function modele(?string $kategoria): array
    {
        return NarzedziaTyp::query()
            ->when($kategoria === null,
                fn ($q) => $q->where(fn ($w) => $w->whereNull('kategoria')->orWhere('kategoria', '')),
                fn ($q) => $q->where('kategoria', $kategoria))
            ->orderBy('name')
            ->withCount(['narzedzias as sztuk'])
            ->get(['id', 'name'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'sztuk' => (int) $t->sztuk])
            ->all();
    }
}
