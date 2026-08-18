<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Ustawienia/Index', [
            'prognozaMaxWorkers' => (int) Setting::get(Setting::PROGNOZA_MAX_WORKERS, 200),
        ]);
    }

    public function update()
    {
        $data = Request::validate([
            'prognoza_max_workers' => ['required', 'integer', 'min:1', 'max:100000'],
        ], [
            'prognoza_max_workers.required' => 'Podaj maksymalną liczbę pracowników.',
            'prognoza_max_workers.integer' => 'Wartość musi być liczbą całkowitą.',
            'prognoza_max_workers.min' => 'Wartość musi być większa od zera.',
            'prognoza_max_workers.max' => 'Wartość jest zbyt duża.',
        ]);

        Setting::put(Setting::PROGNOZA_MAX_WORKERS, (int) $data['prognoza_max_workers']);

        return Redirect::route('ustawienia')->with('success', 'Ustawienia zapisane.');
    }
}
