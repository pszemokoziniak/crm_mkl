<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;

class MonthResult implements JsonSerializable
{
    public function __construct(private int $hours = 0)
    {
    }

    public function add(int $hours): void
    {
        $this->hours += $hours;
    }

    public function hours(): int
    {
        return $this->hours;
    }

    public function jsonSerialize(): array
    {
        return [
          'hours' => $this->hours
        ];
    }
}
