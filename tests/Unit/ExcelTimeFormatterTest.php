<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use App\Services\Date\ExcelTimeFormatter;
use DateTime;
use PHPUnit\Framework\TestCase;

class ExcelTimeFormatterTest extends TestCase
{
    /**
     *
     * @param string $date
     * @param string $formattedTo
     * @return void
     * @throws \Exception
     */
    #[DataProvider('timeDataProvider')]
    public function testShouldFormatDateToExcelFormat(string $date, string $formattedTo): void
    {
        self::assertEquals(
            $formattedTo,
            (new ExcelTimeFormatter())::dateToInteger(new DateTime($date))
        );
    }

    public static function timeDataProvider(): iterable
    {
        yield [
          '17:00', '17,0'
        ];

        yield [
          '17:30', '17,5'
        ];

        yield [
          '09:30', '9,5'
        ];
    }
}