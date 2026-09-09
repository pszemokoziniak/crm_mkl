<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ShiftStatus;
use App\Services\StatystykiBudow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Podsumowania budów: ile godzin przepracowano, ile poszło na urlopy,
 * zwolnienia i nieobecności. Kierownik widzi tylko swoje budowy — zakres
 * bierze się z tego samego miejsca co na liście budów.
 */
class StatystykiController extends Controller
{
    public function index(Request $request, StatystykiBudow $statystyki): Response
    {
        $lata = $statystyki->dostepneLata();
        $wybranyRok = $request->input('rok', 'wszystko');
        $rok = $wybranyRok === 'wszystko' ? null : (int) $wybranyRok;
        $zArchiwum = $request->input('zakres', 'wszystkie') !== 'aktywne';

        return Inertia::render('Statystyki/Index', [
            'budowy' => $statystyki->dlaBudow(Auth::user(), $rok, $zArchiwum),
            'lata' => $lata,
            'filters' => ['rok' => (string) $wybranyRok, 'zakres' => $zArchiwum ? 'wszystkie' : 'aktywne'],
            // Statusy bez kategorii wpadają do "inne" — mówimy o tym wprost,
            // żeby liczba w tej kolumnie nie wyglądała na błąd.
            'statusyBezKategorii' => ShiftStatus::whereNull('kategoria')
                ->orderBy('title')->pluck('title')->all(),
        ]);
    }
}
