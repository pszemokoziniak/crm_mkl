<?php

declare(strict_types=1);

namespace App\Constraints;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HolidayConstraint implements CalendarConstraintInterface
{
    public function __construct(
        private Collection $holidays,
        private CarbonInterface $day
    ) {
    }

    public function isAllowed(): bool
    {
        return $this->holidays->some(fn($holiday) => $this->day->between($holiday->start, $holiday->end));
    }

    public function getType(): string
    {
        return 'holiday';
    }
}
