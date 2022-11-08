<?php

namespace App\Services;

use App\DTO\BuildingTimeSheet as BuildingTimeSheetDTO;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildTimeShiftCreator
{
    public function create(int $build, CarbonPeriod $period): iterable
    {
        $workersOnBuild = $this->getAllWorkersOnBuild($build, $period);
        $buildWorkersShifts = $this->getWorkersOnBuildShifts($build);

        // steps to prepare data
        $buildWorkersSavedShifts = array_reduce($buildWorkersShifts->toArray(), static function ($carry, $item) {
            $carry[$item->id][Carbon::create($item->work_day)->day] = $item;
            return $carry;
        }, []);

        $workersOnBuildData = array_reduce($workersOnBuild->toArray(), static function ($carry, $worker) use ($period) {
            $carry[$worker->id] = [
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name,
                'work_start' => $worker->start,
                'work_end' => $worker->end ?? $period->last()->format('Y-m-d'),
            ];
            return $carry;
        }, []);
        // merge shifts with workers without shifts
        $buildWorkersSavedShifts = $buildWorkersSavedShifts + $workersOnBuildData;

        // build data for calendar
        foreach ($buildWorkersSavedShifts as $workerId => $buildWorkersShifts) {
            foreach ($period as $day) {

                // status out of worker time on building ?
                $isBlocked = !$day->between(
                    Carbon::createFromFormat('Y-m-d', $workersOnBuildData[$workerId]['work_start']),
                    Carbon::createFromFormat('Y-m-d', $workersOnBuildData[$workerId]['work_end'])
                );

                if (
                    array_key_exists($day->day, $buildWorkersShifts)
                    && Carbon::create($buildWorkersShifts[$day->day]->work_day)->isSameDay($day)
                ) {
                    $buildWorkersSavedShifts[$workerId][$day->day] = new BuildingTimeSheetDTO(
                        id: $buildWorkersShifts[$day->day]->id,
                        build: $build,
                        name: $buildWorkersShifts[$day->day]->first_name . ' ' . $buildWorkersShifts[$day->day]->last_name,
                        day: $day,
                        workFrom: $buildWorkersShifts[$day->day]->work_from ?? null,
                        workTo: $buildWorkersShifts[$day->day]->work_to ?? null,
                        work: $buildWorkersShifts[$day->day]->effective_work_time ?? null,
                        status: $buildWorkersShifts[$day->day]->shift_status_id ?? null,
                        isBlocked: $isBlocked
                    );
                    continue;
                }
                $buildWorkersSavedShifts[$workerId][$day->day] = new BuildingTimeSheetDTO(
                    id: $workerId,
                    build: $build,
                    name: $workersOnBuildData[$workerId]['first_name']  . ' '  . $workersOnBuildData[$workerId]['last_name'],
                    day: $day,
                    isBlocked: $isBlocked
                );
            }
        }
        // filter worker data from previous steps
        return $this->filterDayShiftsData($buildWorkersSavedShifts);
    }

    public function getAllWorkersOnBuild(int $build, CarbonPeriod $date): Collection
    {
        return DB::table('contacts', 'c')
            ->join('contact_work_dates', 'c.id', '=', 'contact_work_dates.contact_id')
            ->where('contact_work_dates.organization_id', $build)
            ->whereDate(column: 'start', operator: '>=', value: $date->first()->format('Y-m-d'))
            ->whereDate(column: 'end', operator: '<=', value: $date->last()->format('Y-m-d'))
            ->orWhereNull(column: 'end')
            ->get();
    }

    public function getWorkersOnBuildShifts(int $build): Collection
    {
        // @TODO where is month ? - limit query
        return DB::table('building_time_sheets', 'b')
            ->where('b.organization_id', $build)
            ->join('contacts', 'contacts.id', '=', 'b.contact_id')
            ->orderBy('b.contact_id')
            ->orderBy('b.work_day')
            ->get();
    }


    /**
     * Filter previous generated data to returns only days indexed shifts
     * I think it will be refactored
     *
     * @param mixed $buildWorkersSavedShifts
     * @return array
     */
    public function filterDayShiftsData(mixed $buildWorkersSavedShifts): array
    {
        return array_map(static function ($workerShift) {
            return array_filter($workerShift, static function ($el) {
                return is_numeric($el);
            }, ARRAY_FILTER_USE_KEY);
        }, $buildWorkersSavedShifts);
    }
}
