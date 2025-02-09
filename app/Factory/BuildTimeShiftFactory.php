<?php

declare(strict_types=1);

namespace App\Factory;

use App\Services\BuildTimeShiftCreator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BuildTimeShiftFactory
{
    public static function create(int $build, ?string $date): iterable
    {
        $date = self::getBuildDate($date);

        return (new BuildTimeShiftCreator())->create(
            $build,
            CarbonPeriod::create(
                $date->clone()->toImmutable()->firstOfMonth(),
                $date->clone()->toImmutable()->lastOfMonth()
            )
        );
    }

    public static function getBuildDate(?string $date): Carbon
    {
        return $date ? Carbon::createFromFormat('Y-m-d', $date . '-1') : Carbon::now();
    }
}
