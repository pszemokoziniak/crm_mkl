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

        $timeSheets = [];
        foreach ($workersOnBuild as $worker) {
            // query (I'm sure it's a subquery
            foreach ($month as $day) {
                $timeSheets[$worker->id][$day->day] = [
                    "name" => $worker->first_name . ' ' . $worker->last_name,
                    "id" => $worker->id,
                    "day" => $day,
                    "month" => $day->month,
                    "from" => null,
                    "to" => null,
                    "work" => null,
                ];
            }
        }

        // $dbTimeSheets = DB::table('building_work_time')->get();
        // as default current month
        return Inertia::render('Building/Index.vue',
            [
                'date'       => $date,
                'month'      => $date->monthName,
                'timeSheets' => $timeSheets,
                'build'      => $build,
            ]
        );
    }

    public function store(Request $request)
    {
        /**
         * @see #using factory: app/Http/Controllers/AccountsController.php:96
         * @see #using validated request: app/Http/Controllers/FunkcjaController.php:74
         */
        try {
            BuildingTimeSheetModel::create($request->all());
        } catch (\Exception $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }

        return new JsonResponse(['status' => 'ok']);
    }
}
