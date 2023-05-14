<?php

declare(strict_types=1);

namespace App\Services;

use App\Constraints\CalendarConstraintInterface;
use App\Constraints\FeastDaysConstraint;
use App\Constraints\ShiftOutWorkDatesConstraint;
use App\DTO\Shift;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildTimeShiftCreator
{
    public function create(int $build, CarbonPeriod $period): iterable
    {
        $shifts = $this->getWorkersOnBuildShifts($build, $period);
        $buildWorkersSavedShifts = $this->transform($shifts);
        $feasts = $this->getFeasts($build);
        $workersOnBuildData = $this->workersData($build, $period);

        $buildWorkersSavedShifts = $buildWorkersSavedShifts + $workersOnBuildData;

        foreach ($buildWorkersSavedShifts as $workerId => $shifts) {
            foreach ($period as $day) {

                $constraints = new Collection();
                $constraints->add(new FeastDaysConstraint($feasts, $day));
                $constraints->add(new ShiftOutWorkDatesConstraint(
                    $day,
                    Carbon::createFromFormat('Y-m-d', $workersOnBuildData[$workerId]['work_start']),
                    Carbon::createFromFormat('Y-m-d', $workersOnBuildData[$workerId]['work_end'])
                ));

                $constraintResult = $this->checkConstraints($constraints);
                $isBlocked = (bool) $constraintResult;

                $dayIndex = $day->day;

                if (
                    array_key_exists($dayIndex, $shifts)
                    && Carbon::create($shifts[$dayIndex]->work_day)->isSameDay($day)
                ) {
                    $buildWorkersSavedShifts[$workerId][$dayIndex] = Shift::createFromShift(
                        $shifts[$dayIndex],
                        $build,
                        $isBlocked,
                        $constraintResult?->getType()
                    );
                    continue;
                }

                $buildWorkersSavedShifts[$workerId][$dayIndex] = Shift::createDraft(
                    id: $workerId,
                    build: $build,
                    fullName: $workersOnBuildData[$workerId]['first_name']  . ' '  . $workersOnBuildData[$workerId]['last_name'],
                    day: $day->toString(),
                    isBlocked: $isBlocked,
                    blockedType: $constraintResult?->getType()
                );

            }
        }
        return $this->filterDayShiftsData($buildWorkersSavedShifts);
    }

    private function checkConstraints(Collection $constraints): ?CalendarConstraintInterface
    {
        return $constraints->first(fn($constraint) => $constraint->isAllowed());
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

    /**
     * Returns associative array for each worker by index [index][]days
     *
     * @param Collection $buildWorkersShifts
     * @return mixed
     */
    public function transform(Collection $buildWorkersShifts): iterable
    {
        return array_reduce($buildWorkersShifts->toArray(), static function ($carry, $item) {
            $carry[$item->id][Carbon::create($item->work_day)->day] = $item;
            return $carry;
        }, []);
    }

    /**
     * Return main workers data from build
     *
     * @param int $build
     * @param CarbonPeriod $period
     * @return iterable
     */
    public function workersData(int $build, CarbonPeriod $period): iterable
    {
        $workersOnBuild = $this->getAllWorkersOnBuild($build, $period);

        return array_reduce($workersOnBuild->toArray(), static function ($carry, $worker) use ($period) {
            $carry[$worker->id] = [
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name,
                'work_start' => $worker->start,
                'work_end' => $worker->end ?? $period->last()->format('Y-m-d'),
            ];
            return $carry;
        }, []);
    }


    private function getAllWorkersOnBuild(int $build, CarbonPeriod $date): Collection
    {
        $query = DB::table('contacts', 'c')
            ->join('contact_work_dates', 'c.id', '=', 'contact_work_dates.contact_id')
            ->where('contact_work_dates.organization_id', $build)
            ->whereDate(column: 'start', operator: '<=', value: $date->first()->format('Y-m-d'))
            ->whereDate(column: 'end', operator: '<=', value: $date->last()->format('Y-m-d'));

        return $query->get();
    }

    private function getWorkersOnBuildShifts(int $build, CarbonPeriod $period): Collection
    {
        return DB::table('building_time_sheets', 'b')
            ->join('contacts', 'contacts.id', '=', 'b.contact_id')
            ->where('b.organization_id', $build)
            ->whereBetween('work_day', [$period->first()->format('Y-m-d'), $period->last()->format('Y-m-d')])
            ->orderBy('b.contact_id')
            ->orderBy('b.work_day')
            ->get();
    }

    private function getFeasts(int $build): Collection
    {
        return DB::table('feasts', 'f')
            ->join('kraj_typs', 'kraj_typs.id', '=', 'f.country_id')
            ->join('organizations', 'organizations.country_id', 'kraj_typs.id')
            ->where('organizations.id', $build)
            ->get();
    }
}
