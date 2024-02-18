<?php

declare(strict_types=1);

namespace App\Services\Date;

class ExcelTimeFormatter
{
    /**
     * Formatting 09:30 -> 9,5
     *
     * @param \DateTime $dateTime
     * @return string
     */
    public static function dateToInteger(\DateTime $dateTime): string
    {
        $minutesAsInt = ((int) $dateTime->format('i') <= 30 && (int) $dateTime->format('i') > 0) ? .5 : 0;
        $hoursAsInt = (int) $dateTime->format('H');

        $time = $hoursAsInt + $minutesAsInt;

        return number_format($time, 1, ',', '');
    }
}