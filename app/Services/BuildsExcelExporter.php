<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BuildsExcelExporter
{
    private Spreadsheet $spreadsheet;
    private Worksheet $activeWorksheet;

    public function __construct()
    {
        $this->createSpreadSheet();
    }

    public function generate(iterable $shifts, CarbonPeriod $date)
    {
        $this
            ->addMainHeaders($date)
            ->addDaysHeader($date)
            ->addData($shifts, $date)
            ->addGeneralFormatting();

        return $this;
    }

    private function addMainHeaders(): self
    {
        $this
            ->activeWorksheet
            ->setCellValue('A2', 'Kolumna 1');

        $this
            ->activeWorksheet
            ->setCellValue('B2', 'Nazwisko');

        $this
            ->activeWorksheet
            ->setCellValue('C2', 'Imię');

        return $this;
    }

    public function export(?string $filename = ''): string
    {
        $path = storage_path('app/export/') . 'general_report.xlsx';
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($path);

        return $path;
    }

    public function createSpreadSheet(): void
    {
        $this->spreadsheet = new Spreadsheet();

        $this->activeWorksheet = $this->spreadsheet->getActiveSheet();
    }

    public function addDaysHeader(CarbonPeriod $period): self
    {
        $days = array_map(static fn(Carbon $carbon) => $carbon->day, $period->toArray());

        $arrayData = [
            $days
        ];
        $this->activeWorksheet
            ->fromArray(
                $arrayData,
                NULL,
                'D2'
            );

        $this
            ->activeWorksheet
            ->getStyle('A2:AK2')
            ->applyFromArray([
                'font' => [
                    "color" => ["argb" => Color::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '5b9bd5']
                ]
            ]);

        return $this;
    }

    private function addData(iterable $shifts, CarbonPeriod $period): self
    {
        $startingRowId = 3;

        $period->count();
        /** @var Collection $shift */
        foreach ($shifts as $shift) {
            /** @var [ 2 => 386 ] $dayToCode */
            $rowForWorker = $shift->reduce(function ($carry, $item) {
                $carry[Carbon::create($item->work_day)->day] = $item->code ?? $item->numerBud;
                return $carry;
            }, array_fill(3, $period->count(), ''));

            ksort($rowForWorker);

            $firstName = $shift->first()->first_name;
            $lastName = $shift->first()->last_name;

            $this->activeWorksheet
                ->fromArray(
                    [
                        $rowForWorker
                    ],
                    NULL,
                    'D' . $startingRowId
                );
            $this->activeWorksheet->setCellValue('B' . $startingRowId, $lastName);
            $this->activeWorksheet->setCellValue('C' . $startingRowId, $firstName);

            $this
                ->activeWorksheet
                ->getStyle('A' . $startingRowId . ':' . 'AK' . $startingRowId)
                ->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => $startingRowId % 2 !== 0 ? 'bdd6ee' : 'deeaf6']
                    ]
                ]);

            $startingRowId++;
        }

        return $this;
    }

    private function addGeneralFormatting(): self
    {
        foreach (range('A', 'AK') as $col) {
            $this->activeWorksheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this
            ->activeWorksheet
            ->getStyle('A:AK')
            ->getAlignment()
            ->setHorizontal('center');

        return $this;
    }
}