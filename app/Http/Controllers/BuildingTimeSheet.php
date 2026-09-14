<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Factory\BuildTimeShiftFactory;
use App\Http\Requests\BuildTimeShiftRequest;
use App\Services\BuildsExcelExporter;
use App\Services\BuildTimeShiftCreator;
use App\Services\BuildTimeShiftsExcelExporter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\BuildingTimeSheet as BuildingTimeSheetModel;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class BuildingTimeSheet extends Controller
{
    public function view(int $build, Request $request): Response
    {
        $date = $request->query->get('date');
        $timeShifts = BuildTimeShiftFactory::create($build, $date);
        $date = BuildTimeShiftFactory::getBuildDate($date);

        return Inertia::render('Building/Index',
            [
                'date' => $date,
                'month' => $date->monthName,
                'timeSheets' => $timeShifts,
                'timeSheetsOrder' => array_keys((array)$timeShifts),
                'build' => $build,
                'shiftStatuses' => $this->getShiftStatuses()->all(),
                'user_owner' => Auth::user()->owner,
                'diffDays' => Carbon::today()->diffInDays($date),
                'buildDetails' => $this->getBuildHeaders($build)
            ]
        );
    }

    public function store(BuildTimeShiftRequest $request): JsonResponse
    {
        try {
            BuildingTimeSheetModel::updateOrCreate(
                [
                    'organization_id' => $request->get('build'),
                    'contact_id' => $request->get('id'),
                    'work_day' => new \DateTimeImmutable($request->get('day'))
                ],
                [
                    'work_from' => (new \DateTimeImmutable($request->get('day')))->setTime(
                        (int)$request->get('from')['hours'],
                        (int)$request->get('from')['minutes']),
                    'work_to' => (new \DateTimeImmutable($request->get('day')))->setTime(
                        (int)$request->get('to')['hours'],
                        (int)$request->get('to')['minutes']
                    ),
                    'shift_status_id' => $request->get('status') ?? null,
                    'effective_work_time' => sprintf('%02d:%02d', (int)$request->get('workTime')['hours'], (int)$request->get('workTime')['minutes']),
                    'reduced_working_hours' => $request->get('reducedWorkingHours') ?? false
                ]
            );
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'ok']);
    }

    public function delete(BuildTimeShiftRequest $request): RedirectResponse
    {
        $work_day = new DateTimeImmutable($request->get('day'));
        echo $work_day->format('Y-m-d H:i:s');
        BuildingTimeSheetModel::where('organization_id', $request->get('build'))
            ->where('contact_id', $request->get('id'))
            ->where('work_day', $work_day->format('Y-m-d H:i:s'))->delete();

        return Redirect::back()->with('success', 'Godziny pracy usunięte.');
    }

    public function excelExport(int $build, Request $request): BinaryFileResponse
    {
        $date = $request->query->get('date');

        $timeShifts = BuildTimeShiftFactory::create($build, $date);
        $buildForDate = BuildTimeShiftFactory::getBuildDate($date);
        $shiftStatuses = $this->getShiftStatuses()->all();

        $buildName = $this->getBuildHeaders($build)->nazwaBud;

        $plik = (new BuildTimeShiftsExcelExporter($shiftStatuses))
            ->generate($timeShifts, $buildForDate, $buildName)
            ->export();

        // Nazwa pliku mówi, czego dotyczy: dotąd każdy eksport nazywał się
        // "kcp.xlsx" i po pobraniu kilku nie dało się ich rozróżnić.
        $nazwa = sprintf(
            'KCP %s %s.xlsx',
            preg_replace('/[^\p{L}\p{N} _-]+/u', '', (string) $buildName),
            $buildForDate->format('Y-m')
        );

        return response()->download($plik, $nazwa)->deleteFileAfterSend(true);
    }

    public function reportIndex(): Response
    {
        return Inertia::render('Reports/MonthReport');
    }

    public function buildsReport(Request $request): BinaryFileResponse
    {
        $date = BuildTimeShiftFactory::getBuildDate(
            $request->query->get('date')
        );

        $period = CarbonPeriod::create(
            $date->clone()->toImmutable()->firstOfMonth(),
            $date->clone()->toImmutable()->lastOfMonth()
        );

        $result = $this
            ->getWorkersOnBuildForPeriod($period)
            ->groupBy('contact_id');

        $plik = (new BuildsExcelExporter())
            ->generate($result, $period, $this->bezWpisowWMiesiacu($period, $result->keys()->all()))
            ->export();

        $nazwa = 'Podsumowanie miesiaca '.$period->first()->format('Y-m').'.xlsx';

        return response()->download($plik, $nazwa)->deleteFileAfterSend(true);
    }

    /**
     * Pracownicy przypisani w tym miesiącu do budowy, którzy nie mają ani
     * jednego wpisu w KCP. Bez nich brak wypełnionego miesiąca wygląda w
     * raporcie jak brak pracownika, a to właśnie tych ludzi trzeba dopytać.
     *
     * @param  array<int, int|string>  $zWpisami
     */
    private function bezWpisowWMiesiacu(CarbonPeriod $period, array $zWpisami): Collection
    {
        $pierwszy = $period->first()->format('Y-m-d');
        $ostatni = $period->last()->format('Y-m-d');

        return DB::table('contact_work_dates', 'cwd')
            ->join('contacts', 'contacts.id', '=', 'cwd.contact_id')
            ->join('organizations', 'organizations.id', '=', 'cwd.organization_id')
            ->whereNull('cwd.deleted_at')
            ->whereNull('contacts.deleted_at')
            ->whereDate('cwd.start', '<=', $ostatni)
            ->where(function ($query) use ($pierwszy) {
                $query->whereNull('cwd.end')->orWhereDate('cwd.end', '>=', $pierwszy);
            })
            ->whereNotIn('contacts.id', $zWpisami ?: [0])
            ->select('contacts.id', 'contacts.first_name', 'contacts.last_name', 'organizations.nazwaBud')
            ->orderBy('contacts.last_name')->orderBy('contacts.first_name')
            ->get()
            ->unique('id')
            ->values();
    }

    private function getShiftStatuses(): Collection
    {
        return DB::table('shift_status', 's')->where('deleted_at', null)->get();
    }

    private function getBuildHeaders(int $buildId): mixed
    {
        return DB::table('organizations', 'o')
            // id potrzebne w nagłówku, żeby dało się wrócić do karty budowy
            ->select('o.id', 'o.nazwaBud', 'o.numerBud')
            ->where('o.id', $buildId)
            ->first();
    }

    /**
     * @param CarbonPeriod $period
     * @return Collection
     */
    public function getWorkersOnBuildForPeriod(CarbonPeriod $period): Collection
    {
        return DB::table('building_time_sheets', 'b')
            ->join('organizations', 'organizations.id', '=', 'b.organization_id')
            ->join('contacts', 'contacts.id', '=', 'b.contact_id')
            ->leftJoin('shift_status', 'shift_status.id', '=', 'b.shift_status_id')
            ->whereBetween('work_day', [$period->first()->format('Y-m-d'), $period->last()->format('Y-m-d')])
            ->select(
                'contact_id', 'work_day', 'numerBud', 'organizations.nazwaBud',
                'code', 'shift_status.title as status_nazwa',
                'first_name', 'last_name', 'effective_work_time'
            )
            ->orderBy('contacts.last_name')
            ->orderBy('contacts.first_name')
            ->orderBy('b.work_day')
            ->get();
    }
}
