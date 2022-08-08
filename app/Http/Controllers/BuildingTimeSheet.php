<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;


class BuildingTimeSheet extends Controller
{
    public function view(int $build): Response
    {
        $month = CarbonPeriod::create(Carbon::now()->firstOfMonth(), Carbon::now()->lastOfMonth());
        // fetch for worker on build.

        $timeSheets = [ 1 => []];
        foreach ($month as $day) {
            $timeSheets[1][$day->day] = [
                "id" => 1,
                "day" => $day->day,
                "month" => $day->month,
                "from" => null,
                "to" => null,
                "work" => null,
            ];
        }
        // $dbTimeSheets = DB::table('building_work_time')->get();
        // as default current month
        return Inertia::render('Building/Index.vue', ['timeSheets' => $timeSheets]);
    }

    public function store(Request $request): bool
    {
        return true;
    }
}
