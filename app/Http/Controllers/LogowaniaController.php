<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Logowanie;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Podgląd rejestru logowań — dla administratora.
 */
class LogowaniaController extends Controller
{
    /** Po ilu miesiącach wpisy przestają być potrzebne. */
    public const MIESIACE_PRZECHOWYWANIA = 12;

    public function index(): Response
    {
        $filtry = Request::all('szukaj', 'wynik', 'od', 'do');

        return Inertia::render('Logowania/Index', [
            'filters' => $filtry,
            'nieudane_7dni' => Logowanie::where('udane', false)
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
            'miesiace_przechowywania' => self::MIESIACE_PRZECHOWYWANIA,
            'logowania' => Logowanie::with('user')
                ->filter($filtry)
                ->orderByDesc('created_at')
                ->paginate(50)
                ->withQueryString()
                ->through(fn (Logowanie $wpis) => [
                    'id' => $wpis->id,
                    'kiedy' => optional($wpis->created_at)->format('Y-m-d H:i:s'),
                    'kto' => $wpis->user
                        ? trim($wpis->user->first_name.' '.$wpis->user->last_name)
                        : null,
                    'user_id' => $wpis->user_id,
                    'email' => $wpis->email,
                    'udane' => $wpis->udane,
                    'powod' => $wpis->powod,
                    'ip' => $wpis->ip,
                    'przegladarka' => $wpis->przegladarka,
                ]),
        ]);
    }
}
