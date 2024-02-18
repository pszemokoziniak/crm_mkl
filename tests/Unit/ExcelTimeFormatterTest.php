<?php

namespace Tests\Unit;

use App\Services\Date\ExcelTimeFormatter;
use DateTime;
use PHPUnit\Framework\TestCase;

class ExcelTimeFormatterTest extends TestCase
{
    /**
     * @dataProvider timeDataProvider
     *
     * @param string $date
     * @param string $formattedTo
     * @return void
     * @throws \Exception
     */
    public function testShouldFormatDateToExcelFormat(string $date, string $formattedTo): void
    {
        self::assertEquals(
            $formattedTo,
            (new ExcelTimeFormatter())::dateToInteger(new DateTime($date))
        );
    }

    public function timeDataProvider(): iterable
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