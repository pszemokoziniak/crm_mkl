<?php

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$workersMonthTimeShifts = json_decode(file_get_contents('times_sheets.json'), JSON_THROW_ON_ERROR);

$testTimeAsInt = new \DateTime('2023-02-08 11:30:00'); // 11,5

/**
 * Format to excel
 *
 * @param DateTime $dateTime
 * @return string
 */
function dateToInteger(\DateTime $dateTime): string
{
    $minutesAsInt = ((int) $dateTime->format('i') <= 30 && (int) $dateTime->format('i') > 0) ? .5 : 0;
    $hoursAsInt = (int) $dateTime->format('H');
    return (string) ($hoursAsInt + $minutesAsInt);
}

$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();
$monthForWorker = $workersMonthTimeShifts[14]; // ID 14 - full month

function cellCoordinatesGenerator(int $startsFrom = 65): Generator
{
    $indicators = [$startsFrom];

    while (true) {
        yield implode('', array_map('chr', $indicators));

        if (1 === count($indicators)) {
            $indicators[0]++;
        }

        if (2 === count($indicators)) {
            $indicators[1]++;
        }

        if (1 === count($indicators) && $indicators[0] > 90) {
            $indicators = [65, 65];
        }

        if (2 === count($indicators) && $indicators[1] > 90) {
            $indicators = [66, 65];
        }
    }
}

function workerRowsGenerator(int $startRow = 1): Generator
{
    $cursor = $startRow;

    while (true) {
        yield [
            'work_hours' => $cursor,
            'work_time' => $cursor + 1,
            'work_paid' => $cursor + 2,
        ];

        $cursor += 3;
    }
}

// main headers
$activeWorksheet->setCellValue('A'. 7, 'LP');
$activeWorksheet->setCellValue('B'. 7, 'Imię i nazwisko');
// add supervisor title and project name

// generate month columns
$daysHeadersGenerator = cellCoordinatesGenerator(68);


$daysRow = 7;
foreach (range(1, count($monthForWorker)) as $key => $day) {
    $firstCell =  $daysHeadersGenerator->current();
    $daysHeadersGenerator->next();
    $secondCell = $daysHeadersGenerator->current();


    $firstCellCoords = $firstCell . $daysRow;
    $secondCellCoords = $secondCell . $daysRow;
    $value = $key + 1;

    $activeWorksheet->setCellValue($firstCellCoords, $value);
    $activeWorksheet->setCellValue($secondCellCoords, $value);

    $daysHeadersGenerator->next();

    $activeWorksheet->mergeCells($firstCellCoords . ':' . $secondCellCoords);
}

$workersDataCursor = workerRowsGenerator(8);

var_dump(count($workersMonthTimeShifts));

foreach ($workersMonthTimeShifts as $workerId => $workerShifts) {
    /**
     * Probably each worker needs few rows for all results:
     * - czas pracy od do
     * - czas pracy
     * - placone za
     */
    $rows = $workersDataCursor->current();

    $rowNumber = $rows['work_hours']; // czas pracy od do
    $workingHoursRow = $rows['work_time'];
    $paidFor = $rows['work_paid'];
    $cellIndicatorGenerator = cellCoordinatesGenerator(68);

    $activeWorksheet->setCellValue('A'. $rowNumber, $workerId);
    $activeWorksheet->setCellValue('B'. $rowNumber, reset($workerShifts)['name']);
    $activeWorksheet->setCellValue('C'. $rowNumber, 'czas pracy od/do');

    $activeWorksheet->setCellValue('C'. $workingHoursRow, 'czas pracy');
    $activeWorksheet->setCellValue('C'. $paidFor, 'płacone za');

    foreach ($workerShifts as $key => $shift) {

        $cellCoordsFrom = $cellIndicatorGenerator->current();
        $cellIndicatorGenerator->next();
        $cellCoordsTo = $cellIndicatorGenerator->current();
        $cellIndicatorGenerator->next();

        $activeWorksheet->setCellValue($cellCoordsFrom . $rowNumber, (new \DateTime($shift['from']))->format('G:i')); // format to hours
        $activeWorksheet->setCellValue($cellCoordsTo . $rowNumber, (new \DateTime($shift['to']))->format('G:i')); // format to hours only

        // placone za
        // czas pracy

        // sobota na zólto
        // niedziela na czerwono
    }
    $workersDataCursor->next();
}

$activeWorksheet->freezePane('D1');

foreach (range('A','D') as $col) {
    $activeWorksheet->getColumnDimension($col)->setAutoSize(true);
}


$writer = new Xlsx($spreadsheet);
$writer->save('kcp.xlsx');
