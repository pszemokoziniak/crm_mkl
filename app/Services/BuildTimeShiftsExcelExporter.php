<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Shift;
use App\Services\Date\ExcelTimeFormatter;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BuildTimeShiftsExcelExporter
{
    private Worksheet $activeWorksheet;
    private Spreadsheet $spreadsheet;
    private Excel $rowsGenerator;

    private array $shiftStatuses;
    private array $borderStyleThin = [
        'borders' => [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => Color::COLOR_BLACK],
            ],
        ]
    ];

    public function __construct(array $shiftStatuses)
    {
        $this->createSpreadSheet();
        $this->rowsGenerator = new Excel();
        $this->shiftStatuses = $shiftStatuses;
    }

    public function generate(iterable $shifts, Carbon $date, string $buildName): static
    {
        $this
            ->addMainHeaders($date, $buildName)
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

    private function addMainHeaders(Carbon $date, string $buildName): self
    {
        $titlesRow = 7;

        // build name
        $this
            ->activeWorksheet
            ->setCellValue('B5', 'Projekt: ' . $buildName);
        // created by
        $this
            ->activeWorksheet
            ->setCellValue('D5', 'Sporządził: ');

        $this
            ->activeWorksheet
            ->mergeCells('D5' . ':' . 'I5');

        // main headers
        $this
            ->activeWorksheet
            ->setCellValue('L3', 'ZESTAWIENIE PRZEPRACOWANYCH GODZIN ' . $date->format('Y/m'));

        $this
            ->activeWorksheet
            ->mergeCells('L3' . ':' . 'V3');

        $this
            ->activeWorksheet
            ->setCellValue('A' . $titlesRow, 'LP');

        $this
            ->activeWorksheet
            ->setCellValue('B' . $titlesRow, 'Imię i nazwisko');

        $this
            ->activeWorksheet
            ->getStyle('A' . $titlesRow . ':' . 'C' . $titlesRow)
            ->applyFromArray($this->borderStyleThin);
        // add supervisor title and project name
        return $this;
    }

    public function createSpreadSheet(): void
    {
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->getDefaultStyle()->getFont()->setBold(true);

        $this->activeWorksheet = $this->spreadsheet->getActiveSheet();
    }

    private function addDaysHeaders(iterable $shifts): static
    {
        $daysHeadersGenerator = $this->rowsGenerator->cellCoordinatesGenerator(68);

        $shifts = (array)$shifts;
        $monthForWorker = reset($shifts);

        $daysRow = 7;
        foreach (range(1, count($monthForWorker)) as $key => $day) {
            $firstCell = $daysHeadersGenerator->current();
            $daysHeadersGenerator->next();
            $secondCell = $daysHeadersGenerator->current();

            $firstCellCoords = $firstCell . $daysRow;
            $secondCellCoords = $secondCell . $daysRow;
            $value = $key + 1;

            $this->activeWorksheet->setCellValue($firstCellCoords, $value);
            $this->activeWorksheet->setCellValue($secondCellCoords, $value);
            $this->activeWorksheet->mergeCells($firstCellCoords . ':' . $secondCellCoords);

            $this->activeWorksheet
                ->getStyle($firstCellCoords . ':' . $secondCellCoords)
                ->applyFromArray($this->borderStyleThin)
                ->getAlignment()
                ->setHorizontal('center');


            $daysHeadersGenerator->next();
        }

        $sumCell = $daysHeadersGenerator->current();

        $this
            ->activeWorksheet
            ->setCellValue($sumCell . $daysRow, 'SUMA');

        $this
            ->activeWorksheet
            ->getStyle($sumCell . $daysRow)
            ->applyFromArray($this->borderStyleThin)
            ->getAlignment()
            ->setHorizontal('center');

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

        $workerIterator = 1;
        foreach ($shifts as $workerId => $workerShifts) {

            /**
             * Probably each worker needs few rows for all results:
             * - czas pracy od do
             * - czas pracy
             * - placone za
             */
            $rows = $workersDataCursor->current();

            $workHoursRow = $rows['work_hours'];
            $workingHoursRow = $rows['work_time'];
            $paidFor = $rows['work_paid'];
            $cellIndicatorGenerator = $this->rowsGenerator->cellCoordinatesGenerator(68);

            $first = $cellIndicatorGenerator->current();

            $this->activeWorksheet->setCellValue('A' . $workHoursRow, $workerIterator);
            $this->activeWorksheet->setCellValue('B' . $workHoursRow, reset($workerShifts)->name);
            $this->activeWorksheet->setCellValue('C' . $workHoursRow, 'czas pracy od/do');

            $this->activeWorksheet->setCellValue('C' . $workingHoursRow, 'czas pracy');
            $this->activeWorksheet->setCellValue('C' . $paidFor, 'płacone za');

            // merge LP, name rows
            $this->activeWorksheet->mergeCells('B'. $workHoursRow . ':' . 'B' . $paidFor);
            $this->activeWorksheet->mergeCells('A'. $workHoursRow . ':' . 'A' . $paidFor);

            $this
                ->activeWorksheet
                ->getStyle('B'. $workHoursRow . ':' . 'B' . $paidFor)
                ->getAlignment()
                ->setVertical('center');

            $this
                ->activeWorksheet
                ->getStyle('C'. $workHoursRow . ':' . 'C' . $paidFor)
                ->applyFromArray($this->borderStyleThin)
                ->getAlignment()
                ->setVertical('center');

            $this
                ->activeWorksheet
                ->getStyle('A'. $workHoursRow . ':' . 'A' . $paidFor)
                ->getAlignment()
                ->setVertical('center');

            $this->activeWorksheet
                ->getStyle('A' . $workHoursRow . ':' . 'A' . $paidFor)
                ->applyFromArray($this->borderStyleThin);

            $this->activeWorksheet
                ->getStyle('B' . $workHoursRow . ':' . 'B' . $paidFor)
                ->applyFromArray($this->borderStyleThin);

            $workPaidSum = 0;

            $workerIterator++;

            ksort($workerShifts); // some days are not in order

            /**
             * @var int $key
             * @var Shift $shift
             */
            foreach ($workerShifts as $key => $shift) {

                $cellCoordsFrom = $cellIndicatorGenerator->current();
                $cellIndicatorGenerator->next();
                $cellCoordsTo = $cellIndicatorGenerator->current();
                $cellIndicatorGenerator->next();

                $cellFrom = $cellCoordsFrom . $workHoursRow;
                $cellTo = $cellCoordsTo . $workHoursRow;

                $this
                    ->activeWorksheet
                    ->getStyle($cellFrom)
                    ->applyFromArray($this->borderStyleThin);

                $this
                    ->activeWorksheet
                    ->getStyle($cellFrom)
                    ->applyFromArray($this->borderStyleThin);

                // no work hours and status - set zeroes
                if (!$shift->workFrom && !$shift->workTo) {
                    $this
                        ->activeWorksheet
                        ->setCellValue($cellCoordsFrom . $paidFor, '0,0');

                    $this
                        ->activeWorksheet
                        ->setCellValue($cellCoordsFrom . $workingHoursRow, '0,0');

                    $this
                        ->activeWorksheet
                        ->getStyle($cellCoordsFrom . $paidFor)
                        ->getAlignment()
                        ->setHorizontal('center');

                    $this
                        ->activeWorksheet
                        ->getStyle($cellCoordsFrom . $workingHoursRow)
                        ->getAlignment()
                        ->setHorizontal('center');
                }

                // merge paid for cells
                $this
                    ->activeWorksheet
                    ->mergeCells($cellCoordsFrom . $paidFor . ':' . $cellCoordsTo . $paidFor);

                $this
                    ->activeWorksheet
                    ->getStyle($cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow)
                    ->applyFromArray($this->borderStyleThin);

                $this
                    ->activeWorksheet
                    ->mergeCells($cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow);

                if ($shift->status) {
                    $this->activeWorksheet
                        ->getStyle($cellFrom . ':' . $cellTo)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(Color::COLOR_DARKGREEN);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(Color::COLOR_DARKGREEN);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $paidFor . ':' . $cellCoordsTo . $paidFor)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(Color::COLOR_DARKGREEN);

                    // set shift status code e.g. UW,OG
                    $foundShifts = array_filter($this->shiftStatuses, static fn($shiftStatus) => $shiftStatus->id === $shift->status);
                    $code = reset($foundShifts)->code;
                    $this->activeWorksheet->setCellValue($cellCoordsFrom . $paidFor, $code);

                    // only merge to align view with original
                    $this
                        ->activeWorksheet
                        ->getStyle($cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow)
                        ->applyFromArray($this->borderStyleThin);

                    $this
                        ->activeWorksheet
                        ->mergeCells($cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow);

                    continue;
                }

                if ($shift->isSaturday()) {
                    $this->activeWorksheet
                        ->getStyle($cellFrom . ':' . $cellTo)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(Color::COLOR_YELLOW);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(Color::COLOR_YELLOW);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $paidFor . ':' . $cellCoordsTo . $paidFor)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(Color::COLOR_YELLOW);
                }

                if ($shift->isSunday()) {
                    $this->activeWorksheet
                        ->getStyle($cellFrom . ':' . $cellTo)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(Color::COLOR_RED);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(Color::COLOR_RED);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $paidFor . ':' . $cellCoordsTo . $paidFor)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(Color::COLOR_RED);
                }

                if ($shift->workFrom) {
                    $this->activeWorksheet->setCellValue(
                        $cellFrom, ExcelTimeFormatter::dateToInteger($shift->workFrom)
                    );
                }

                if ($shift->workTo) {
                    $this->activeWorksheet->setCellValue(
                        $cellTo, ExcelTimeFormatter::dateToInteger($shift->workTo)
                    );
                }

                if ($shift->work) {
                    $this->activeWorksheet->setCellValue(
                        $cellCoordsFrom . $workingHoursRow, ExcelTimeFormatter::dateToInteger($shift->work)
                    );
                }

                if ($shift->work) {
                    $shiftInMinutes = Carbon::createFromFormat('H:i', $shift->work)->diffInMinutes(Carbon::now()->startOfDay());
                    $workPaidSum += $shiftInMinutes;
                }
            }

            // set hours sum - last column
            $this
                ->activeWorksheet
                ->setCellValue($cellIndicatorGenerator->current() . $workHoursRow, ((int)$workPaidSum / 60));

            $this
                ->activeWorksheet
                ->getStyle($cellIndicatorGenerator->current() . $workHoursRow)
                ->applyFromArray($this->borderStyleThin)
                ->getAlignment()
                ->setHorizontal('center');

            $this
                ->activeWorksheet
                ->getStyle($cellIndicatorGenerator->current() . $workHoursRow)
                ->applyFromArray($this->borderStyleThin)
                ->getAlignment()
                ->setHorizontal('center');

            $this
                ->activeWorksheet
                ->getStyle($cellIndicatorGenerator->current() . $paidFor)
                ->applyFromArray($this->borderStyleThin)
                ->getAlignment()
                ->setHorizontal('center');

            // border for worker rows
            $this
                ->activeWorksheet
                ->getStyle($first . $workHoursRow  . ':' . $cellIndicatorGenerator->current() . $paidFor)
                ->applyFromArray([
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => Color::COLOR_BLACK],
                        ],
                        'top' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => Color::COLOR_BLACK],
                        ],
                        'right' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => Color::COLOR_BLACK],
                        ],
                    ]
                ]);

            $this
                ->activeWorksheet
                ->getStyle('A' . $workHoursRow . ':' . 'C' . $paidFor)
                ->applyFromArray([
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => Color::COLOR_BLACK],
                        ],
                        'top' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => Color::COLOR_BLACK],
                        ],
                        'left' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => Color::COLOR_BLACK],
                        ],
                    ]
                ]);

            $workersDataCursor->next();
        }

        return $this;
    }

    private function addGeneralFormatting(): static
    {
        $this->activeWorksheet->freezePane('D1');

        foreach (range('A', 'D') as $col) {
            $this->activeWorksheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->activeWorksheet->getStyle('A:Z')
            ->getAlignment()
            ->setHorizontal('center');

        return $this;
    }
}