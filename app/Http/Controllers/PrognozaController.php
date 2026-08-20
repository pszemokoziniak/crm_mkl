<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrognozaRequest;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\Prognoza;
use App\Models\PrognozaDates;
use App\Models\Setting;
use App\Services\PrognozaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
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
            // Bez wybranego roku pokazujemy wszystko, co jest wpisane — zakres kończymy
            // na ostatnim tygodniu z prognozą, żeby nagłówek zgadzał się z wykresem.
            // Wcześniej sztywne "dziś + 6 lat" obiecywało datę, do której nic nie sięgało.
            $startDate = $currentYear->copy()->startOfYear();
            $ostatniTydzien = $this->ostatniTydzienPrognozy($building);

            $endDate = $ostatniTydzien && $ostatniTydzien->gt($startDate)
                ? $ostatniTydzien
                : $currentYear->copy()->endOfYear();
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

        // Rok podajemy tylko wtedy, gdy użytkownik go wybrał. Wcześniej szedł tu
        // domyślny rok bieżący i wykres ucinał prognozy z kolejnych lat, mimo że
        // tabela pod spodem i zakres w nagłówku już je obejmowały.
        $chartLabels = $this->getChartLabels($building, $yearSelected, $monthSelected, $startDate, $endDate);

        // Chronologicznie — grupowanie szło po id wpisu, więc kolejność słupków
        // zależała od tego, kto i kiedy wpisywał prognozę.
        $chartLabels = $chartLabels->sortBy(fn ($group) => $group['prognoza_dates']['start'])->values();

        // Na osi sam początek tygodnia; pełny zakres trafia do dymka, bo dwie daty
        // w każdej etykiecie zlewały się w nieczytelną kaszę.
        $labels = $chartLabels->map(fn ($group) => $group['prognoza_dates']['start'])->toArray();

        $ranges = $chartLabels->map(
            fn ($group) => $group['prognoza_dates']['start'].' – '.$group['prognoza_dates']['end']
        )->toArray();

        $dataChart = $chartLabels->map(fn ($group) => $group['total_workers'])->toArray();

        $chartData = [
            'labels' => $labels,
            'ranges' => $ranges,
            'datasets' => [
                [
                    'label' => 'Liczba pracowników',
                    'backgroundColor' => '#42A5F5',
                    'data' => $dataChart,
                ]
            ]
        ];

        // withTrashed — prognoza zarchiwizowanej budowy ma dalej pokazywać jej nazwę,
        // inaczej relacja wraca pusta i lista się sypie.
        $data = Prognoza::with(['organization' => fn ($query) => $query->withTrashed(), 'prognozadates'])
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

        $chartMax = (int) Setting::get(Setting::PROGNOZA_MAX_WORKERS, 200);

        return Inertia('Prognoza/Index', compact('years', 'yearSelected', 'months', 'monthSelected', 'data', 'selectedBuild', 'buildings', 'chartData', 'startDate', 'endDate', 'startDateFormat', 'endDateFormat', 'chartMax'));
    }

    public function create()
    {
        $buildingId = request()->query('building');

        $building = Organization::where('id', $buildingId)->get()->map->only(['id', 'nazwaBud']);

        // Bez konkretnej budowy nie ma czego prognozować — formularz nie miałby nazwy budowy.
        if ($building->isEmpty()) {
            return Redirect::route('prognoza', request()->only('year', 'month'))
                ->with('error', 'Najpierw wybierz budowę.');
        }

        $currentYear = Carbon::now();
        $dates = $this->getSelectDates($currentYear, $buildingId);

        return Inertia('Prognoza/Create', compact('dates', 'building'));
    }

    public function store(StorePrognozaRequest $request)
    {
        Prognoza::create([
            'organization_id' => $request->building_id,
            'prognoza_dates_id' => $request->prognoza_dates_id,
            'workers_count' => $request->workers_count,
        ]);

        return Redirect::route('prognoza', ['building'=>$request->building_id, 'year'=>$request->year_id, 'month'=>$request->month_id])->with('success', 'Prognoza dodana.');
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

    /**
     * Koniec ostatniego tygodnia, na który ktoś wpisał prognozę
     * (dla wybranej budowy albo wszystkich). Null, gdy nie ma żadnej.
     */
    private function ostatniTydzienPrognozy($building): ?Carbon
    {
        $ostatni = PrognozaDates::query()
            ->whereIn('id', Prognoza::query()
                ->when($building !== 'all', fn ($query) => $query->where('organization_id', $building))
                ->select('prognoza_dates_id'))
            ->max('end');

        return $ostatni ? Carbon::parse($ostatni) : null;
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

    /**
     * Lata do wyboru — tylko te, dla których mamy w bazie tygodnie.
     * Wcześniej lista szła 7 lat w przód i trafiała na roczniki bez tygodni,
     * gdzie lista dat była pusta bez żadnego wyjaśnienia.
     */
    function getCalendarYears($currentYear)
    {
        $years = PrognozaDates::query()
            ->distinct()
            ->where('year', '>=', $currentYear->year)
            ->orderBy('year')
            ->pluck('year')
            ->all();

        return $years ?: [$currentYear->year];
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

    /**
     * Podzakładka "Prognoza" na budowie: zapotrzebowanie (plan) tydzień po
     * tygodniu zestawione z faktyczną obsadą (contact_work_dates).
     */
    public function budowaShow(Organization $organization)
    {
        $flag = Auth::user()->owner === 3; // kierownik — tylko podgląd

        $prognozas = Prognoza::with('prognozadates')
            ->where('organization_id', $organization->id)
            ->get()
            ->sortBy(fn ($p) => optional($p->prognozadates)->start)
            ->values();

        // Pobyty tej budowy raz; obsadę per tydzień liczymy w PHP.
        $stays = ContactWorkDate::where('organization_id', $organization->id)
            ->get(['contact_id', 'start', 'end']);

        $rows = $prognozas->map(function ($p) use ($stays) {
            $wStart = optional($p->prognozadates)->start;
            $wEnd = optional($p->prognozadates)->end;

            $assigned = 0;
            if ($wStart && $wEnd) {
                $assigned = $stays->filter(function ($s) use ($wStart, $wEnd) {
                    // Pobyt zachodzi na tydzień: start <= koniec tygodnia oraz
                    // (brak końca albo koniec >= początek tygodnia).
                    return $s->start
                        && $s->start <= $wEnd
                        && ($s->end === null || $s->end >= $wStart);
                })->pluck('contact_id')->unique()->count();
            }

            return [
                'id' => $p->id,
                'start' => $wStart,
                'end' => $wEnd,
                'workers_count' => (int) $p->workers_count,
                'assigned' => $assigned,
            ];
        })->values();

        $chartData = [
            'labels' => $rows->pluck('start')->toArray(),
            'ranges' => $rows->map(fn ($r) => $r['start'].' – '.$r['end'])->toArray(),
            'datasets' => [
                [
                    'label' => 'Zapotrzebowanie',
                    'backgroundColor' => '#6574cd',
                    'data' => $rows->pluck('workers_count')->toArray(),
                ],
                [
                    'label' => 'Obsadzeni',
                    'backgroundColor' => '#4caf7d',
                    'data' => $rows->pluck('assigned')->toArray(),
                ],
            ],
        ];

        // Formularz dodawania — wolne tygodnie dla wybranego roku/miesiąca.
        $currentYear = Carbon::now();

        return Inertia::render('Prognoza/Budowa', [
            'build' => $organization->id,
            'buildDetails' => $organization,
            'rows' => $rows,
            'chartData' => $chartData,
            'years' => $this->getCalendarYears($currentYear),
            'months' => $this->getCalendarMonths($currentYear),
            'freeWeeks' => $this->getSelectDates($currentYear, $organization->id)
                ->map->only(['id', 'start', 'end'])->values(),
            'filters' => [
                'year' => request()->query('year'),
                'month' => request()->query('month'),
            ],
            'flag' => $flag,
        ]);
    }

    public function budowaStore(StorePrognozaRequest $request, Organization $organization)
    {
        Prognoza::create([
            'organization_id' => $organization->id,
            'prognoza_dates_id' => $request->prognoza_dates_id,
            'workers_count' => $request->workers_count,
        ]);

        return Redirect::route('budowy.prognoza', $organization->id)->with('success', 'Prognoza dodana.');
    }

    public function budowaUpdate(Organization $organization, Prognoza $prognoza)
    {
        $prognoza->update(
            Request::validate([
                'workers_count' => ['required', 'numeric', 'gt:0', 'max:500'],
            ])
        );

        return Redirect::route('budowy.prognoza', $organization->id)->with('success', 'Poprawiono.');
    }

    public function budowaDestroy(Organization $organization, Prognoza $prognoza)
    {
        $prognoza->delete();

        return Redirect::route('budowy.prognoza', $organization->id)->with('success', 'Usunięto.');
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
