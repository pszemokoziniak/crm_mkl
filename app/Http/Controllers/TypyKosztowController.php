<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TypKosztu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Ustawienia → Typy kosztów: nazwa plus dwa przełączniki (dzielony, nocleg). */
class TypyKosztowController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('TypyKosztow/Index', [
            'typy' => TypKosztu::withCount('koszty')->orderBy('nazwa')->get()
                ->map(fn (TypKosztu $t) => [
                    'id' => $t->id, 'nazwa' => $t->nazwa, 'dzielony' => $t->dzielony,
                    'nocleg' => $t->nocleg, 'uzyc' => $t->koszty_count,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TypKosztu::create($this->dane($request));

        return Redirect::route('typyKosztow')->with('success', 'Typ kosztu dodany.');
    }

    public function update(Request $request, TypKosztu $typ): RedirectResponse
    {
        $typ->update($this->dane($request, $typ));

        return Redirect::route('typyKosztow')->with('success', 'Typ kosztu poprawiony.');
    }

    public function destroy(TypKosztu $typ): RedirectResponse
    {
        if ($typ->koszty()->exists()) {
            return Redirect::route('typyKosztow')
                ->with('error', "Typ „{$typ->nazwa}” jest użyty na kosztach — nie da się go usunąć.");
        }

        $typ->delete();

        return Redirect::route('typyKosztow')->with('success', 'Typ kosztu usunięty.');
    }

    /** @return array{nazwa: string, dzielony: bool, nocleg: bool} */
    private function dane(Request $request, ?TypKosztu $typ = null): array
    {
        $d = $request->validate([
            'nazwa' => ['required', 'string', 'max:100', Rule::unique('typy_kosztow', 'nazwa')->ignore($typ?->id)->whereNull('deleted_at')],
            'dzielony' => ['nullable', 'boolean'],
            'nocleg' => ['nullable', 'boolean'],
        ]);

        return [
            'nazwa' => trim($d['nazwa']),
            // Pokój zawsze dzieli się po zakwaterowanych.
            'dzielony' => (bool) ($d['dzielony'] ?? false) || (bool) ($d['nocleg'] ?? false),
            'nocleg' => (bool) ($d['nocleg'] ?? false),
        ];
    }
}
