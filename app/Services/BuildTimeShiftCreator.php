<?php

declare(strict_types=1);

namespace App\Services;

use App\Constraints\CalendarConstraintInterface;
use App\Constraints\FeastDaysConstraint;
use App\Constraints\HolidayConstraint;
use App\Constraints\ShiftOutWorkDatesConstraint;
use App\Constraints\SundayConstraint;
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
        // enrich workers which are assigned to build with another date period (inconsistent data)
        $workersOnBuildData = $this->workersData($build, $period);

        $buildWorkersSavedShifts = $buildWorkersSavedShifts + $workersOnBuildData;

        $buildWorkersSavedShifts = collect($buildWorkersSavedShifts)->sortBy(static function ($item, $key) {
            // property based on worker data (no shift)
            if (array_key_exists('last_name', $item)) {
                return $item['last_name'];
            }
            // last_name based on shift data - first element
            return ((array) $item[array_key_first($item)])['last_name'];
        }, SORT_NATURAL)
        ->toArray();

        // Cache shift status ID for 'UW' (Urlop wypoczynkowy)
        $holidayStatusId = DB::table('shift_status')->where('code', 'UW')->value('id');

        foreach ($buildWorkersSavedShifts as $workerId => $shifts) {
            $holidays = $this->getHolidays($workerId, $period);
            foreach ($period as $day) {
                $constraints = new Collection();
                $constraints->add(new FeastDaysConstraint($feasts, $day));
                $constraints->add(new SundayConstraint($day));
                $holidayConstraint = new HolidayConstraint($holidays, $day);
                $constraints->add($holidayConstraint);

                if (isset($workersOnBuildData[$workerId])) {
                    $constraints->add(new ShiftOutWorkDatesConstraint(
                        $day,
                        Carbon::parse($workersOnBuildData[$workerId]['work_start'])->startOfDay(),
                        Carbon::parse($workersOnBuildData[$workerId]['work_end'])->startOfDay()
                    ));
                }

                $constraintResult = $this->checkConstraints($constraints);
                $isBlocked = (bool)$constraintResult;
                $blockedType = $constraintResult?->getType();
                $isHolidayType = $blockedType === 'holiday';

                $dayIndex = $day->day;

                if (
                    array_key_exists($dayIndex, $shifts)
                    && Carbon::create($shifts[$dayIndex]->work_day)->isSameDay($day)
                ) {
                    $shift = $shifts[$dayIndex];
                    // If no status in DB, but it is a holiday, use holiday status.
                    // Only if no work time is recorded.
                    $hasWork = !empty($shift->effective_work_time) && $shift->effective_work_time !== '00:00';

                    if (!$shift->shift_status_id && $isHolidayType && !$hasWork) {
                        $shift->shift_status_id = $holidayStatusId;
                    }

                    $buildWorkersSavedShifts[$workerId][$dayIndex] = Shift::createFromShift(
                        $shift,
                        $build,
                        $isBlocked,
                        $blockedType
                    );
                    continue;
                }

                $status = null;
                if ($isHolidayType) {
                    $status = $holidayStatusId;
                }

                // Fallback for name if worker data is missing (should not happen for drafts as drafts imply no shift, so worker must be in workersOnBuildData)
                // But if we are here, it means no shift for this day.
                // If workerId is not in workersOnBuildData, it means they have shifts on OTHER days but not this one, AND no contract data.
                // We need a name.
                $fullName = 'Unknown';
                if (isset($workersOnBuildData[$workerId])) {
                    $fullName = $workersOnBuildData[$workerId]['last_name'] . ' ' . $workersOnBuildData[$workerId]['first_name'];
                } elseif (!empty($shifts)) {
                     // Try to get name from existing shifts
                     $firstShift = reset($shifts);
                     if ($firstShift instanceof \stdClass) {
                         $fullName = $firstShift->last_name . ' ' . $firstShift->first_name;
                     }
                }

                $buildWorkersSavedShifts[$workerId][$dayIndex] = Shift::createDraft(
                    id: $workerId,
                    build: $build,
                    fullName: $fullName,
                    day: $day->toString(),
                    isBlocked: $isBlocked,
                    blockedType: $blockedType,
                    status: $status
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
            $carry[$item->contact_id][Carbon::create($item->work_day)->day] = $item;
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
        $query = DB::table('contact_work_dates', 'cwd')
            ->join('contacts', 'cwd.contact_id', '=', 'contacts.id')
            ->where('cwd.organization_id', $build)
//             @TODO to review data - workers on build
//            ->whereDate(column: 'start', operator: '<=', value: $date->last()->format('Y-m-d'))
//            ->whereDate(column: 'end', operator: '>=', value: $date->first()->format('Y-m-d'))
            ->orderBy('contacts.last_name', 'ASC')
            ->get();

        return $query;
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
            ->join('organizations', 'organizations.country_id', '=', 'kraj_typs.id')
            ->where('organizations.id', $build)
            ->select('f.*')
            ->get();
    }

    private function getHolidays(int $workerId, CarbonPeriod $period): Collection
    {
        return DB::table('holidays')
            ->where('contact_id', $workerId)
            ->where(function ($query) use ($period) {
                $query->where('start', '<=', $period->last()->format('Y-m-d'))
                      ->where('end', '>=', $period->first()->format('Y-m-d'));
            })
            ->get();
    }
}
