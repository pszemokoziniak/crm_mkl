<?php

declare(strict_types=1);

namespace App\Services\Date;

use DateTime;

class ExcelTimeFormatter
{
    /**
     * Formatting 09:30 -> 9,5
     *
     * @param DateTime|string $dateTime
     * @return string
     * @throws \Exception
     */
    public static function dateToInteger(DateTime|string $dateTime): string
    {
        if (is_string($dateTime)) {
            $dateTime = new DateTime($dateTime);
        }

        $minutesAsInt = ((int) $dateTime->format('i') <= 30 && (int) $dateTime->format('i') > 0) ? .5 : 0;
        $hoursAsInt = (int) $dateTime->format('H');

        $time = $hoursAsInt + $minutesAsInt;

        return number_format($time, 1, ',', '');
    }
}