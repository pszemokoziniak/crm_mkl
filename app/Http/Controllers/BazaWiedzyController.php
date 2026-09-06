<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Artykul;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Baza wiedzy — instrukcje i procedury w jednym miejscu.
 * Czytać może każdy zalogowany, pisać tylko administrator.
 */
class BazaWiedzyController extends Controller
{
    public function index(): Response
    {
        $szukaj = Request::input('szukaj');

        $artykuly = Artykul::widoczneDla(Auth::user())
            ->szukaj($szukaj)
            ->ulozone()
            ->get()
            ->map(fn (Artykul $a) => [
                'id' => $a->id,
                'tytul' => $a->tytul,
                'kategoria' => $a->kategoria,
                'tylko_admin' => $a->tylko_admin,
                'zajawka' => $a->zajawka(),
                'zmieniony' => optional($a->updated_at)->format('Y-m-d'),
            ]);

        return Inertia::render('BazaWiedzy/Index', [
            'filters' => ['szukaj' => $szukaj],
            'artykuly' => $artykuly,
            // Grupujemy dopiero na ekranie, ale kolejność kategorii ustala
            // baza — inaczej "Bez kategorii" lądowałoby raz tu, raz tam.
            'kategorie' => $artykuly->pluck('kategoria')->unique()->values(),
        ]);
    }

    public function show(Artykul $artykul): Response
    {
        $this->sprawdzDostep($artykul);

        return Inertia::render('BazaWiedzy/Show', [
            'artykul' => [
                'id' => $artykul->id,
                'tytul' => $artykul->tytul,
                'kategoria' => $artykul->kategoria,
                'tylko_admin' => $artykul->tylko_admin,
                'html' => $artykul->html(),
                'zmieniony' => optional($artykul->updated_at)->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('BazaWiedzy/Create', [
            'kategorie' => Artykul::whereNotNull('kategoria')
                ->distinct()->orderBy('kategoria')->pluck('kategoria'),
        ]);
    }

    public function store(): RedirectResponse
    {
        $artykul = Artykul::create($this->dane());

        return Redirect::route('bazaWiedzy.show', $artykul)->with('success', 'Artykuł dodany.');
    }

    public function edit(Artykul $artykul): Response
    {
        return Inertia::render('BazaWiedzy/Edit', [
            'artykul' => [
                'id' => $artykul->id,
                'tytul' => $artykul->tytul,
                'kategoria' => $artykul->kategoria,
                'tresc' => $artykul->tresc,
                'tylko_admin' => $artykul->tylko_admin,
                'kolejnosc' => $artykul->kolejnosc,
            ],
            'kategorie' => Artykul::whereNotNull('kategoria')
                ->distinct()->orderBy('kategoria')->pluck('kategoria'),
        ]);
    }

    public function update(Artykul $artykul): RedirectResponse
    {
        $artykul->update($this->dane());

        return Redirect::route('bazaWiedzy.show', $artykul)->with('success', 'Artykuł zapisany.');
    }

    public function destroy(Artykul $artykul): RedirectResponse
    {
        $artykul->delete();

        return Redirect::route('bazaWiedzy')->with('success', 'Artykuł usunięty.');
    }

    /** @return array<string, mixed> */
    private function dane(): array
    {
        $dane = Request::validate([
            'tytul' => ['required', 'string', 'max:255'],
            'kategoria' => ['nullable', 'string', 'max:80'],
            'tresc' => ['nullable', 'string'],
            'tylko_admin' => ['boolean'],
            'kolejnosc' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        // Puste pola z formularza przychodzą jako null, a kolumny mają
        // wartości domyślne, nie NULL.
        $dane['kolejnosc'] = (int) ($dane['kolejnosc'] ?? 0);
        $dane['tylko_admin'] = (bool) ($dane['tylko_admin'] ?? false);

        return $dane;
    }

    /**
     * Artykuł tylko dla admina ma dla reszty nie istnieć — 403 zdradzałoby,
     * że pod tym adresem coś jest.
     */
    private function sprawdzDostep(Artykul $artykul): void
    {
        abort_if($artykul->tylko_admin && ! optional(Auth::user())->isAdmin(), 404);
    }
}
