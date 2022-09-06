<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;

class BuildingTimeSheet implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $build,
        public string $name,
        public int $day,
        public int $month,
        public ?string $workFrom = null,
        public ?string $workTo = null,
        public ?string $work = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'build' => $this->build,
            'name' => $this->name,
            'day' => $this->day,
            'month' => $this->month,
            'from' => $this->workFrom,
            'to' => $this->workTo,
            'work' => $this->work
        ];
    }
}
