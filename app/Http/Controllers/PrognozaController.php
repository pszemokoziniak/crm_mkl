<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Prognoza;
use App\Models\PrognozaDates;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class PrognozaController extends Controller
{
    public function index()
    {
        $currentYear = Carbon::now();

        (!isset($_GET['year'])) ? $year = $currentYear->year : $year = $_GET['year'];
        (!isset($_GET['month'])) ? $month = $currentYear->month : $month = $_GET['month'];

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $years = $this->getCalendarYears($currentYear);
        $months = $this->getCalendarMonths($currentYear);
        $buildings = Organization::get()->map->only(['id', 'nazwaBud']);
        $selectedBuild = isset($_GET['building']) ? $this->getUrlBuildParams($_GET['building']) : [0, 'wybierz'];
//        $data = $this->getSelectDates($currentYear);

        $data = Prognoza::with(['organization', 'prognozadates'])
            ->whereHas('prognozadates', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start', [$startDate, $endDate]);
            })
            ->get()->map(function ($prognoza) {
            return [
                'id' => $prognoza->id,
                'prognozadates' => $prognoza->prognozadates,
                'organization' => $prognoza->organization,
                'workers_count' => $prognoza->workers_count,
            ];
        });

        return Inertia('Prognoza/Index', compact('years', 'months', 'data', 'buildings', 'selectedBuild'));
    }

    public function create()
    {
        $building = Organization::where('id', $_GET['building'])->get()->map->only(['id', 'nazwaBud']);
        $currentYear = Carbon::now();
        $dates = $this->getSelectDates($currentYear);

        return Inertia('Prognoza/Create', compact('dates', 'building'));
    }

    public function store(Request $request)
    {
        Prognoza::create([
            'organization_id' => $request->building_id,
            'prognoza_dates_id' => $request->dates,
            'workers_count' => $request->workers_count,
        ]);

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
                    PrognozaDates::create([
                        'start' => $monday,
                        'end' => $sunday,
                        'year' => $currentYear,
                    ]);
                }
            }
        }
    }

    function getUrlBuildParams($id)
    {
        return Organization::where('id', $id)->get()->map->only(['id', 'nazwaBud'])->first();
    }

    function getSelectDates($currentYear)
    {
        (!isset($_GET['year'])) ? $year = $currentYear->year : $year = $_GET['year'];
        (!isset($_GET['month'])) ? $month = $currentYear->month : $month = $_GET['month'];

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        return PrognozaDates::where('year', $year)
            ->whereBetween('start', [$startDate, $endDate])
            ->get();
    }

    function getCalendarYears($currentYear)
    {
        $years = array();

        for ($i = 0; $i < 7; $i++) {
            $years[] = $currentYear->copy()->addYears($i)->year;
        }
        return $years;
    }

    function getCalendarMonths($currentYear)
    {
        $months = array();
        $currentYearStart = Carbon::now()->startOfYear();

        for ($i = 0; $i < 12; $i++) {
            $months[] = $currentYearStart->copy()->addMonths($i)->month;
        }
        return $months;
    }

//    function building(Request $request)
//    {
////        dd($request);
//        $buildings = Organization::get()->map->only(['id', 'name']);
//        $selected = $request->query('selected', 3); // Default value
//        return Inertia::render('Prognoza/Index', [
//            'initialSelected' => $selected,
//            'buildings' => $buildings,
//        ]);
//    }
}
