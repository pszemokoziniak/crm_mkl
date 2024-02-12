<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Factory\BuildTimeShiftFactory;
use App\Http\Requests\BuildTimeShiftRequest;
use App\Services\BuildTimeShiftCreator;
use App\Services\BuildTimeShiftsExcelExporter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $date = $request->query->get('date')
            ? Carbon::createFromTimeString($request->query->get('date') . 1)
            : Carbon::now();

        $timeShifts = (new BuildTimeShiftCreator())->create(
            $build,
            CarbonPeriod::create(
                $date->clone()->toImmutable()->firstOfMonth(),
                $date->clone()->toImmutable()->lastOfMonth()
            )
        );

        return Inertia::render('Building/Index.vue',
            [
                'date' => $date,
                'month' => $date->monthName,
                'timeSheets' => $timeShifts,
                'build' => $build,
                'shiftStatuses' => $this->getShiftStatuses()->all(),
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
        $timeShifts = BuildTimeShiftFactory::create($build, $request->query->get('date'));

        return response()->file(
            (new BuildTimeShiftsExcelExporter())->build($timeShifts)->export()
        );
    }

    private function getShiftStatuses(): Collection
    {
        return DB::table('shift_status', 's')->get();
    }
}
