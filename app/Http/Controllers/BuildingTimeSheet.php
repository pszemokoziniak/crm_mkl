<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        // fetch for worker on build.
        $workersOnBuild = DB::table('contacts')
            ->where('organization_id', $build)
            ->get();

        $buildWorkersShifts = DB::table('building_time_sheets', 'b')
            ->where('b.organization_id', $build)
            ->join('contacts', 'contacts.id', '=', 'b.contact_id')
            ->orderBy('b.contact_id')
            ->orderBy('b.work_day')
            ->get();

        $buildWorkers = array_reduce($buildWorkersShifts->toArray(), static function ($carry, $item) {
            $carry[$item->id][] = $item;
            return $carry;
        }, []);

        $timeSheets = [];
        foreach ($buildWorkers as $buildWorkersShifts) {
            foreach ($buildWorkersShifts as $workersShift) {
                foreach ($month as $day) {
                    $shiftDay = Carbon::create($workersShift->work_day);
                    if ($shiftDay->isSameDay($day)) {
                        // @TODO formatting on FE ?
                        $timeSheets[$workersShift->id][$day->day] = [
                            'build' => $build,
                            "name" => $workersShift->first_name . ' ' . $workersShift->last_name,
                            "id" => $workersShift->id,
                            "day" => $day,
                            "month" => $day->month,
                            "from" => $workersShift->work_from ?? null,
                            "to" => $workersShift->work_to ?? null,
                            "work" => $workersShift->effective_work_time ?? null,
                        ];

                        continue;
                    }

                    $timeSheets[$workersShift->id][$day->day] = [
                        'build' => $build,
                        "name" => $workersShift->first_name . ' ' . $workersShift->last_name,
                        "id" => $workersShift->id,
                        "day" => $day,
                        "month" => $day->month,
                        "from" => null,
                        "to" => null,
                        "work" => null,
                    ];
                }
            }
        }

        return Inertia::render('Building/Index.vue',
            [
                'date' => $date,
                'month' => $date->monthName,
                'timeSheets' => $timeSheets,
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

        $workDay = new \DateTimeImmutable($data['day']);

        $splitFrom = explode(':', $data['from']);
        $splitTo = explode(':', $data['to']);

        $dataToSave = [
            'organization_id'       => $data['build'],
            'contact_id'            => $data['id'],
            'work_day'              => $workDay,
            'work_from'             => clone $workDay->setTime((int) $splitFrom[0], (int) $splitFrom[1]),
            'work_to'               => clone $workDay->setTime((int) $splitTo[0], (int) $splitTo[1]),
            'effective_work_time'   => $data['workTime'],
        ];

        try {
            BuildingTimeSheetModel::create($dataToSave);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'ok']);
    }
}
