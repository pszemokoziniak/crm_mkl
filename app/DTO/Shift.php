<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;
use JsonSerializable;
use phpDocumentor\Reflection\Utils;

class Shift implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $build,
        public string $name,
        public string $day,
        public ?string $workFrom = null,
        public ?string $workTo = null,
        public ?string $work = null,
        public ?int $status = null,
        public ?bool $isBlocked = null,
        public ?string $blockedType = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'build' => $this->build,
            'name' => $this->name,
            'day' => $this->day,
            'from' => $this->workFrom,
            'to' => $this->workTo,
            'work' => $this->work,
            'status' => $this->status,
            'isBlocked' => $this->isBlocked,
            'blockedType' => $this->blockedType,
        ];
    }

    public static function createFromShift(\stdClass $shift, int $build, bool $isBlocked, ?string $blockedType): self
    {
        return new self(
            id: $shift->id,
            build: $build,
            name: $shift->first_name . ' ' . $shift->last_name,
            day: $shift->work_day,
            workFrom: $shift->work_from ?? null,
            workTo: $shift->work_to ?? null,
            work: $shift->effective_work_time ?? null,
            status: $shift->shift_status_id ?? null,
            isBlocked: $isBlocked,
            blockedType: $blockedType,
        );
    }

    public static function createDraft(int $id, int $build, string $fullName, string $day, bool $isBlocked, ?string $blockedType): Shift
    {
        return new self(
            id: $id,
            build: $build,
            name: $fullName,
            day: $day,
            isBlocked: $isBlocked,
            blockedType: $blockedType,
        );
    }

    public function isSunday(): bool
    {
        return Carbon::create($this->day)->isSunday();
    }

    public function isSaturday(): bool
    {
        return Carbon::create($this->day)->isSaturday();
    }
}
