<?php

declare(strict_types=1);

namespace App\Constraints;

use Carbon\CarbonInterface;

class SundayConstraint implements CalendarConstraintInterface
{
    public function __construct(
        private CarbonInterface $day
    ) {
    }

    public function isAllowed(): bool
    {
        return $this->day->isSunday();
    }

    public function getType(): string
    {
        return 'sunday';
    }
}
