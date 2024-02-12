<?php

declare(strict_types=1);

namespace App\Factory;

use App\Services\BuildTimeShiftCreator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BuildTimeShiftFactory
{
    public static function create(int $build, ?string $date)
    {
        $date = $date ? Carbon::createFromTimeString($date . 1) : Carbon::now();

        return  (new BuildTimeShiftCreator())->create(
            $build,
            CarbonPeriod::create(
                $date->clone()->toImmutable()->firstOfMonth(),
                $date->clone()->toImmutable()->lastOfMonth()
            )
        );
    }
}