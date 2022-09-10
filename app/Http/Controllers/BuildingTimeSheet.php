<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\BuildingTimeSheet as BuildingTimeSheetDTO;
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
    public function view(int $build): Response
    {
        $date = Carbon::now()->toImmutable(); // default month from today
        $month = CarbonPeriod::create($date->firstOfMonth(), $date->lastOfMonth());
        $workersOnBuild = $this->getAllWorkersOnBuild($build);

        $buildWorkersShifts = $this->getWorkersOnBuildShifts($build);

        $buildWorkersSavedShifts = array_reduce($buildWorkersShifts->toArray(), static function ($carry, $item) {
            $carry[$item->id][Carbon::create($item->work_day)->day] = $item;
            return $carry;
        }, []);

        $workersOnBuildData = array_reduce($workersOnBuild->toArray(), static function ($carry, $worker) {
            $carry[$worker->id] = [
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name
            ];
            return $carry;
        }, []);

        // @TODO get names of workers
        foreach ($buildWorkersSavedShifts as $workerId => $buildWorkersShifts) {
            foreach ($month as $day) {
                if (array_key_exists($day->day, $buildWorkersShifts)) {
                    $buildWorkersSavedShifts[$workerId][$day->day] = new BuildingTimeSheetDTO(
                        id: $buildWorkersShifts[$day->day]->id,
                        build: $build,
                        name: $buildWorkersShifts[$day->day]->first_name . ' ' . $buildWorkersShifts[$day->day]->last_name,
                        day: $day,
                        workFrom: $buildWorkersShifts[$day->day]->work_from ?? null,
                        workTo: $buildWorkersShifts[$day->day]->work_to ?? null,
                        work: $buildWorkersShifts[$day->day]->effective_work_time ?? null,
                    );
                    continue;
                }
                $buildWorkersSavedShifts[$workerId][$day->day] = new BuildingTimeSheetDTO(
                    id: $workerId,
                    build: $build,
                    name: $workersOnBuildData[$workerId]['first_name']  . ' '  . $workersOnBuildData[$workerId]['last_name'],
                    day: $day,
                );
            }
        }

        return Inertia::render('Building/Index.vue',
            [
                'date' => $date,
                'month' => $date->monthName,
                'timeSheets' => $buildWorkersSavedShifts,
                'build' => $build,
            ]
        );
    }

    public function store(Request $request)
    {
        /**
         * @see #using factory: app/Http/Controllers/AccountsController.php:96
         * @see #using validated request: app/Http/Controllers/FunkcjaController.php:74
         */

        $data = $request->all();
        // @TODO create correct date! not only day but month!
        $workDay = new \DateTimeImmutable($data['day']);

        $splitFrom = explode(':', $data['from']);
        $splitTo = explode(':', $data['to']);

        $dataToSave = [
            'organization_id' => $data['build'],
            'contact_id' => $data['id'],
            'work_day' => $workDay,
            'work_from' => clone $workDay->setTime((int)$splitFrom[0], (int)$splitFrom[1]),
            'work_to' => clone $workDay->setTime((int)$splitTo[0], (int)$splitTo[1]),
            'effective_work_time' => $data['workTime'],
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

    /**
     * Return all workers assigned to build (not shifts)
     *
     * @param int $build
     * @return Collection
     */
    public function getAllWorkersOnBuild(int $build): Collection
    {
        return DB::table('contacts')
            ->where('organization_id', $build)
            ->get();
    }

    /**
     * @param int $build
     * @return Collection
     */
    public function getWorkersOnBuildShifts(int $build): Collection
    {
        return DB::table('building_time_sheets', 'b')
            ->where('b.organization_id', $build)
            ->join('contacts', 'contacts.id', '=', 'b.contact_id')
            ->orderBy('b.contact_id')
            ->orderBy('b.work_day')
            ->get();
    }
}
