<?php

namespace App\Constraints;

interface CalendarConstraintInterface
{
    public function isAllowed(): bool;

    public function getType(): string;
}
