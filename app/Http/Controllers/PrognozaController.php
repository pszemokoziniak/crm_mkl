<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrognozaRequest;
use App\Models\Organization;
use App\Models\Prognoza;
use App\Models\PrognozaDates;
use App\Services\PrognozaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

use stdClass;

class PrognozaController extends Controller
{
    public function __construct()
    {
    }

    public function index()
    {
        $currentYear = Carbon::now();

        $hasYear = request()->has('year');
        $hasMonth = request()->has('month');

        $building = request()->query('building') ?? 'all';
        $year = (int) request()->query('year', $currentYear->year);
        $month = (int) request()->query('month', $currentYear->month);

        if ($hasYear && $hasMonth) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        } elseif ($hasYear && !$hasMonth) {
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = Carbon::createFromDate($year, 1, 1)->endOfYear();
        } else {
            $startDate = $currentYear->copy()->startOfYear();
            $endDate = $currentYear->copy()->addYears(6)->endOfMonth();
        }

        $startDateFormat = $startDate->format('Y-m-d');
        $endDateFormat = $endDate->format('Y-m-d');

        $years = $this->getCalendarYears($currentYear);
        $months = $this->getCalendarMonths($currentYear);

        $buildings = Organization::get()->map->only(['id', 'nazwaBud']);

        // isset($_GET['building']) ? $building = $_GET['building'] : $building = 'all';

        $selectedBuildParams = $this->getUrlBuildParams($building);

        if ($building === 'all' || empty($selectedBuildParams)) {
            $selectedBuild = (object) ['id' => 'all', 'nazwaBud' => 'Wszystkie'];
        } else {
            $selectedBuild = (object) $selectedBuildParams[0];
        }
        $building = request()->query('building') ?? 'all';

//        $year = (int) request()->query('year', $currentYear->year);
//        $month = (int) request()->query('month', $currentYear->month);

        $yearSelected = $hasYear ? $year : null;
        $monthSelected = ($hasYear && $hasMonth) ? $month : null;

        $chartLabels = $this->getChartLabels($building, $year, $month, $startDate, $endDate);

        $labels = $chartLabels->flatMap(function ($data) {
            return [$data['prognoza_dates']['start'] . ' to ' . $data['prognoza_dates']['end']];
        })->toArray();


        $dataChart = $chartLabels->flatMap(function ($group) {
            return [$group['total_workers']];
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

        return Inertia('Prognoza/Index', compact('years', 'yearSelected', 'months', 'monthSelected', 'data', 'selectedBuild', 'buildings', 'chartData', 'startDate', 'endDate', 'startDateFormat', 'endDateFormat'));
    }

    public function create()
    {
        $building = Organization::where('id', $_GET['building'])->get()->map->only(['id', 'nazwaBud']);
        $currentYear = Carbon::now();
        $dates = $this->getSelectDates($currentYear, $_GET['building']);

        return Inertia('Prognoza/Create', compact('dates', 'building'));
    }

    public function store(StorePrognozaRequest $request)
    {
        Prognoza::create([
            'organization_id' => $request->building_id,
            'prognoza_dates_id' => $request->prognoza_dates_id,
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

    function getUrlBuildParams($id)
    {
        return Organization::when($id !== 'all', function ($query) use ($id) {
            $query->where('id', $id);
        })->get()->map->only(['id', 'nazwaBud'])->all();
    }

    function getSelectDates($currentYear, $buildingId)
    {
//        $year = $_GET['year'] ?? $currentYear->year;
//        $month = $_GET['month'] ?? $currentYear->month;

//        $building = request()->query('building', 'all');
        $year = (int) request()->query('year', $currentYear->year);
        $month = (int) request()->query('month', $currentYear->month);

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
            $monthNumber = $i + 1;
            $months[] = [
                'value' => $monthNumber,
                'label' => $currentYearStart->copy()->addMonths($i)->locale('pl_PL')->monthName,
            ];
        }

        return $months;
    }


    function getBuildings(): array
    {
        return Organization::get()->map->only(['id', 'nazwaBud']);
    }

    function prepareTable()
    {
        $years = $this->getCalendarYears(Carbon::now()->startOfYear());
        $buildings = $this->getBuildings();

    }

    private function getChartLabels($building = null, $year = null, $month = null, $startDate = null, $endDate = null)
    {
        $prognozas = app(PrognozaService::class)->getPrognozas($building, $year, $month, $startDate, $endDate);

        $groupedPrognozas = $prognozas->groupBy('prognoza_dates_id')
            ->map(function ($group) {
                return [
                    'total_workers' => $group->sum('workers_count'),
                    'prognoza_dates' => $group->first()->prognozadates, // Assuming you might want to keep this info
                ];
            });

        return $groupedPrognozas;
    }
}
