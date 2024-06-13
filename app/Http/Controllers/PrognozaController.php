<?php

namespace App\Http\Controllers;

use App\Models\Prognoza;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PrognozaController extends Controller
{
    public function index()
    {
        $years = array();
        $currentYear = Carbon::now();

        for ($i = 0; $i < 7; $i++) {
            $years[] = $currentYear->copy()->addYears($i)->year;
        }

        (!isset($_GET['year'])) ? $year = $currentYear->year : $year = $_GET['year'];

        $data = Prognoza::where('year', $year)->get();

        return Inertia('Prognoza/Index', compact('years', 'data'));
    }

    public function edit(Prognoza $prognoza)
    {
        return Inertia::render('Prognoza/Edit', [
            'prognoza' => [
                'id' => $prognoza->id,
                'workers_count' => $prognoza->workers_count,
            ],
        ]);
    }

    public function update(Prognoza $prognoza)
    {
        $year = $prognoza->year;
        $prognoza->update(
            \Illuminate\Support\Facades\Request::validate([
                'workers_count' => ['required', 'numeric', 'max:500'],
            ])
        );
        return Redirect::route('prognoza', ['year' => $year])->with('success', 'Poprawiono.');
    }

    function displayWeeksInYear() {
        $years = [2024, 2025, 2026, 2027, 2028, 2029, 2030];

        foreach ($years as $year) {
            $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfWeek(Carbon::MONDAY);
            $endOfYear = Carbon::createFromDate($year, 12, 31)->endOfWeek(Carbon::SUNDAY);

            $period = CarbonPeriod::create($startOfYear, '1 week', $endOfYear);

            foreach ($period as $date) {
                $monday = $date->copy()->startOfWeek(Carbon::MONDAY);
                $sunday = $date->copy()->endOfWeek(Carbon::SUNDAY);
                $currentYear = $monday->year;

                if ($monday->year == $year) {
                    Prognoza::create([
                        'start' => $monday,
                        'end' => $sunday,
                        'year' => $currentYear,
                    ]);
                }
            }
        }
    }
}
