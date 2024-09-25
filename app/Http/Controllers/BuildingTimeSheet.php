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

        return Inertia::render('Building/Index.vue',
            [
                'date' => $date,
                'month' => $date->monthName,
                'timeSheets' => $timeShifts,
                'build' => $build,
                'shiftStatuses' => $this->getShiftStatuses()->all(),
                'user_owner' => Auth::user()->owner,
                'diffDays' => (int) Carbon::today()->diffInDays($date)
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
                    'effective_work_time' => $request->get('workTime')['hours'] . ':' . $request->get('workTime')['minutes'],
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

        return response()->file(
            (new BuildTimeShiftsExcelExporter($shiftStatuses))
                ->generate($timeShifts, $buildForDate, $buildName)
                ->export()
        );
    }

    public function reportIndex(): Response
    {
        return Inertia::render('Reports/MonthReport.vue');
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

        return response()->file(
            (new BuildsExcelExporter())
                ->generate($result, $period)
                ->export()
        );
    }

    private function getShiftStatuses(): Collection
    {
        return DB::table('shift_status', 's')->where('deleted_at', null)->get();
    }

    private function getBuildHeaders(int $buildId): mixed
    {
        return DB::table('organizations', 'o')
            ->select('o.nazwaBud')
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
            ->select('contact_id', 'work_day', 'numerBud', 'code', 'first_name', 'last_name')
            ->orderBy('b.contact_id')
            ->orderBy('b.work_day')
            ->get();
    }
}
