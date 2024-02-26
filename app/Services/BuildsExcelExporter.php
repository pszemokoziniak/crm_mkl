<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
            ->addData($shifts, $date);

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
        $this->spreadsheet->getDefaultStyle()->getFont()->setBold(true);

        $this->activeWorksheet = $this->spreadsheet->getActiveSheet();
    }

    public function addDaysHeader(CarbonPeriod $period): self
    {
        $days = array_map(static fn(Carbon $carbon) => $carbon->day, $period->toArray());

        $arrayData = [
            $days
        ];
        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                $arrayData,
                NULL,
                'D2'
            );

        return $this;
    }

    private function addData(iterable $shifts, CarbonPeriod $period): self
    {
        $period->count();
        /** @var Collection $worker */
        $worker = $shifts[14];

        /** @var [ 2 => 386 ] $dayToCode */
        $rowForWorker = $worker->reduce(function ($carry, $item) {
            $carry[Carbon::create($item->work_day)->day] = $item->code ?? $item->numerBud;
            return $carry;
        }, array_fill(3, $period->count(), ''));

        ksort($rowForWorker);

        $firstName = $worker->first()->first_name;
        $lastName = $worker->first()->last_name;

        $this->spreadsheet->getActiveSheet()
            ->fromArray(
                [$rowForWorker],
                NULL,
                'D3'
            );

        return $this;
    }
}