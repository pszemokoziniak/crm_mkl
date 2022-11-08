<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\BuildingTimeSheet as BuildingTimeSheetDTO;
use App\Services\BuildTimeShiftCreator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\BuildingTimeSheet as BuildingTimeSheetModel;
use Inertia\Response;

class BuildingTimeSheet extends Controller
{
    public function view(int $build, Request $request): Response
    {
        $date = (Carbon::now())->setMonth($request->query->get('month') ?? Carbon::now()->month);
        $period = $this->generatePeriod($date);

        $buildTimeShiftCreator = new BuildTimeShiftCreator();
        $timeShifts = $buildTimeShiftCreator->create($build, $period);

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

    public function store(Request $request): JsonResponse
    {
        /**
         * @see #using factory: app/Http/Controllers/AccountsController.php:96
         * @see #using validated request: app/Http/Controllers/FunkcjaController.php:74
         */

        $data = $request->all();
        // @TODO create correct date! not only day but month!
        $workDay = new \DateTimeImmutable($data['day']);

        $dataToSave = [
            'organization_id' => $data['build'],
            'contact_id' => $data['id'],
            'work_day' => $workDay,
            'work_from' => clone $workDay->setTime((int)$data['from']['hours'], (int)$data['from']['minutes']),
            'work_to' => clone $workDay->setTime((int)$data['to']['hours'], (int)$data['to']['minutes']),
            'shift_status_id' => $data['status'] ?? null, // id
            'effective_work_time' => $data['workTime']['hours'] . ':' . $data['workTime']['minutes'],
        ];

        try {
            BuildingTimeSheetModel::create($dataToSave);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'ok']);
    }

    private function getShiftStatuses(): Collection
    {
        return DB::table('shift_status', 's')->get();
    }

    /**
     * @param Carbon $date
     * @return CarbonPeriod
     */
    public function generatePeriod(Carbon $date): CarbonPeriod
    {
        return CarbonPeriod::create(
            $date->clone()->toImmutable()->firstOfMonth(),
            $date->clone()->toImmutable()->lastOfMonth()
        );
    }
}
