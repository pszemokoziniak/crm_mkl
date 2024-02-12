<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BuildTimeShiftsExcelExporter
{
    private Worksheet $activeWorksheet;
    private Spreadsheet $spreadsheet;

    public function __construct()
    {
        $this->createSpreadSheet();

        $rowsGenerator = new Excel();
    }

    public function build(array $shifts): self
    {
        $this->addMainHeaders($shifts);

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
}