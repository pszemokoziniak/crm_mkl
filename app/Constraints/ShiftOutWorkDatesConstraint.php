<?php

declare(strict_types=1);

namespace App\Constraints;

use Carbon\CarbonInterface;

class ShiftOutWorkDatesConstraint implements CalendarConstraintInterface
{
    public function __construct(
        private CarbonInterface $calendarDay,
        private CarbonInterface $from,
        private CarbonInterface $to,
    ) {
    }

    public function isAllowed(): bool
    {
        return !$this->calendarDay->between($this->from, $this->to);
    }

    public function getType(): string
    {
        return 'outOfWorkDates';
    }
}
