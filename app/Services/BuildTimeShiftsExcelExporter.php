<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Shift;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BuildTimeShiftsExcelExporter
{
    private Worksheet $activeWorksheet;
    private Spreadsheet $spreadsheet;
    private Excel $rowsGenerator;

    public function __construct()
    {
        $this->createSpreadSheet();

        $this->rowsGenerator = new Excel();
    }

    public function build(iterable $shifts): static
    {
        $this
            ->addMainHeaders($shifts)
            ->addDaysHeaders($shifts)
            ->addWorkersShifts($shifts)
            ->addGeneralFormatting();

        return $this;
    }

    public function export(?string $filename = ''): string
    {
        $path = storage_path('app/export/') . 'kcp.xlsx';
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($path);

        return $path;
    }

    private function addMainHeaders(): self
    {
        // main headers
        $this->activeWorksheet->setCellValue('A'. 7, 'LP');
        $this->activeWorksheet->setCellValue('B'. 7, 'Imię i nazwisko');
        // add supervisor title and project name
        return $this;
    }

    public function createSpreadSheet(): void
    {
        $this->spreadsheet = new Spreadsheet();
        $this->activeWorksheet = $this->spreadsheet->getActiveSheet();
    }

    private function addDaysHeaders(iterable $shifts): static
    {
        $daysHeadersGenerator = $this->rowsGenerator->cellCoordinatesGenerator(68);

        $shifts = (array) $shifts;
        $monthForWorker = reset($shifts);
        
        $daysRow = 7;
        foreach (range(1, count($monthForWorker)) as $key => $day) {
            $firstCell =  $daysHeadersGenerator->current();
            $daysHeadersGenerator->next();
            $secondCell = $daysHeadersGenerator->current();

            $firstCellCoords = $firstCell . $daysRow;
            $secondCellCoords = $secondCell . $daysRow;
            $value = $key + 1;

            $this->activeWorksheet->setCellValue($firstCellCoords, $value);
            $this->activeWorksheet->setCellValue($secondCellCoords, $value);
            $this->activeWorksheet->mergeCells($firstCellCoords . ':' . $secondCellCoords);

            $this->activeWorksheet->getStyle($firstCellCoords . ':' . $secondCellCoords)
                ->getAlignment()
                ->setHorizontal('center');

            $daysHeadersGenerator->next();
        }

        return $this;
    }


    /**
     * @param iterable|Shift[] $shifts
     * @return $this
     * @throws \Exception
     */
    private function addWorkersShifts(iterable $shifts): static
    {
        $workersDataCursor = $this->rowsGenerator->workerRowsGenerator(8);

        foreach ($shifts as $workerId => $workerShifts) {
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
            $cellIndicatorGenerator = $this->rowsGenerator->cellCoordinatesGenerator(68);

            $this->activeWorksheet->setCellValue('A'. $rowNumber, $workerId);
            $this->activeWorksheet->setCellValue('B'. $rowNumber, reset($workerShifts)->name);
            $this->activeWorksheet->setCellValue('C'. $rowNumber, 'czas pracy od/do');

            $this->activeWorksheet->setCellValue('C'. $workingHoursRow, 'czas pracy');
            $this->activeWorksheet->setCellValue('C'. $paidFor, 'płacone za');

            /**
             * @var int $key
             * @var Shift $shift */
            foreach ($workerShifts as $key => $shift) {

                $cellCoordsFrom = $cellIndicatorGenerator->current();
                $cellIndicatorGenerator->next();
                $cellCoordsTo = $cellIndicatorGenerator->current();
                $cellIndicatorGenerator->next();

                if ($shift->workFrom) {
                    $this->activeWorksheet->setCellValue(
                        $cellCoordsFrom . $rowNumber, (new \DateTime($shift->workFrom))->format('G:i')
                    ); // format to hours
                }

                if ($shift->workTo) {
                    $this->activeWorksheet->setCellValue(
                        $cellCoordsTo . $rowNumber, (new \DateTime($shift->workTo))->format('G:i')
                    ); // format to hours only
                }
                // @TODO
                // placone za
                // czas pracy

                // sobota na zólto
                // niedziela na czerwono
            }
            $workersDataCursor->next();
        }

        return $this;
    }

    private function addGeneralFormatting(): static
    {
        $this->activeWorksheet->freezePane('D1');

        foreach (range('A','D') as $col) {
            $this->activeWorksheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this;
    }
}