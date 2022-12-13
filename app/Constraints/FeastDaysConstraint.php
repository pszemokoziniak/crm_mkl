<?php

declare(strict_types=1);

namespace App\Constraints;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FeastDaysConstraint implements CalendarConstraintInterface
{
    public function __construct(
        private Collection $feasts,
        private CarbonInterface $day
    ) {
    }

    public function isAllowed(): bool
    {
        return $this->feasts->some(fn($fest) => $this->day->isSameDay($fest->date));
    }

    public function getType(): string
    {
        return 'feast';
    }
}
