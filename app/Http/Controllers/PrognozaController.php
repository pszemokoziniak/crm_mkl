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

        $building = request()->query('building');
        $year = request()->query('year');
        $chartLabels = Prognoza::with('prognozadates')
            ->when(isset($building), function ($query) use ($building) {
                $query->where('organization_id', $building);
            })
            ->when($year, function ($query, $year) {
                $query->whereHas('prognozadates', function ($query) use ($year) {
                    $query->where('year', $year);
                });
            })
//            ->when($month, function ($query, $startDate, $endDate) {
//                $query->whereHas('prognozadates', function ($query) use ($startDate, $endDate) {
//                    $query->whereBetween('start', [$startDate, $endDate]);
//                });
//            })
            ->when(isset($month), function ($query) use ($startDate, $endDate) {
                $query->whereHas('prognozadates', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start', [$startDate, $endDate]);
                });
            })
//            ->whereHas('prognozadates', function ($query) use ($startDate, $endDate) {
//                $query->whereBetween('start', [$startDate, $endDate]);
//            })
            ->get()->map(function ($prognoza) {
            $prognozadate = $prognoza->prognozadates;
            return [
                'id' => $prognoza->id,
                'organization_id' => $prognoza->organization_id,
                'start' => Carbon::parse($prognozadate->start)->format('Y-m-d'),
                'end' => Carbon::parse($prognozadate->end)->format('Y-m-d'),
                'workers_count' => $prognoza->workers_count,
            ];

        });



        $labels = $chartLabels->map(function ($label) {
            return $label['start'] . ' ' . $label['end'];
        })->toArray();

        $dataChart = $chartLabels->map(function ($label) {
            return $label['workers_count'];
        })->toArray();

        $chartData = [

            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Liczba pracowników',
                    'backgroundColor' => '#42A5F5',
                    'data' => $dataChart,
                ]
            ]
        ];

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

        return Inertia('Prognoza/Index', compact('years', 'months', 'data', 'buildings', 'selectedBuild', 'chartData', 'startDate', 'endDate'));
    }

    public function create()
    {
        $building = Organization::where('id', $_GET['building'])->get()->map->only(['id', 'nazwaBud']);
        $currentYear = Carbon::now();
        $dates = $this->getSelectDates($currentYear, $_GET['building']);

        return Inertia('Prognoza/Create', compact('dates', 'building'));
    }

    public function store(Request $request)
    {
        Prognoza::create([
            'organization_id' => $request->building_id,
            'prognoza_dates_id' => $request->dates,
            'workers_count' => $request->workers_count,
        ]);

        return Redirect::route('prognoza', ['building'=>$request->building_id, 'year'=>$request->year_id, 'month'=>$request->month_id])->with('success', 'Godziny dodane.');
    }

    public function edit(Prognoza $prognoza)
    {
        return Inertia::render('Prognoza/Edit', [
            'prognoza' => [
                'id' => $prognoza->id,
                'workers_count' => $prognoza->workers_count,
                'prognoza_dates_id' => PrognozaDates::where('id', $prognoza->prognoza_dates_id)->get()->map->only(['id', 'start', 'end']),
            ],
        ]);
    }
    public function update(Prognoza $prognoza)
    {
        $date = PrognozaDates::where('id', $prognoza->prognoza_dates_id)->get()->map->only(['start']);
        $startDate = $date->first()['start'];
        $carbonDate = Carbon::parse($startDate);
        $year = $carbonDate->year;
        $month = $carbonDate->month;

        $prognoza->update(
            \Illuminate\Support\Facades\Request::validate([
                'workers_count' => ['required', 'numeric', 'max:500'],
            ])
        );
        return Redirect::route('prognoza', ['year' => $year, 'month' => $month, 'building' => $prognoza->organization_id])->with('success', 'Poprawiono.');
    }

//    function displayWeeksInYear() {
//        $years = [2024, 2025, 2026, 2027, 2028, 2029, 2030];
//
//        foreach ($years as $year) {
//            $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfWeek(Carbon::MONDAY);
//            $endOfYear = Carbon::createFromDate($year, 12, 31)->endOfWeek(Carbon::SUNDAY);
//
//            $period = CarbonPeriod::create($startOfYear, '1 week', $endOfYear);
//
//            foreach ($period as $date) {
//                $monday = $date->copy()->startOfWeek(Carbon::MONDAY);
//                $sunday = $date->copy()->endOfWeek(Carbon::SUNDAY);
//                $currentYear = $monday->year;
//
//                if ($monday->year == $year) {
//                    PrognozaDates::create([
//                        'start' => $monday,
//                        'end' => $sunday,
//                        'year' => $currentYear,
//                    ]);
//                }
//            }
//        }
//    }

    function getUrlBuildParams($id)
    {
        return Organization::where('id', $id)->get()->map->only(['id', 'nazwaBud'])->first();
    }

    function getSelectDates($currentYear, $buildingId)
    {
        !isset($_GET['year']) ? $year = $currentYear->year : $year = $_GET['year'];
        !isset($_GET['month']) ? $month = $currentYear->month : $month = $_GET['month'];

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $prognozaDates = PrognozaDates::where('year', $year)
            ->whereBetween('start', [$startDate, $endDate])
            ->get();
        $arrayPrognozaDates = $prognozaDates->map(function ($prognozaDate) {
            return $prognozaDate->id;
        })->toArray();

        $prognozas = Prognoza::where('organization_id', $buildingId)->get();
        $arrayPrognoza = $prognozas->map(function ($prognoza) {
            return $prognoza->prognoza_dates_id;
        })->toArray();

        $freeDates = array_diff($arrayPrognozaDates, $arrayPrognoza);
        return PrognozaDates::whereIn('id', $freeDates)->get();

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

    function getBuildings(): array
    {
        return Organization::get()->map->only(['id', 'nazwaBud']);
    }

    function prepareTable()
    {
        $years = $this->getCalendarYears(Carbon::now()->startOfYear());
        $buildings = $this->getBuildings();





    }
}
