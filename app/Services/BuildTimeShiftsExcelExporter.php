<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Shift;
use App\Services\Date\ExcelTimeFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BuildTimeShiftsExcelExporter
{
    private Worksheet $activeWorksheet;
    private Spreadsheet $spreadsheet;
    private Excel $rowsGenerator;

    /**
     * Kolory dobrane tak, żeby dało się czytać wpisy: wcześniej dni ze
     * statusem miały ciemnozielone tło pod czarnym tekstem, a niedziele
     * pełną czerwień. Te same odcienie co na ekranie KCP.
     */
    private const TLO_SOBOTA = 'FFFFF2CC';
    private const TLO_NIEDZIELA = 'FFFBE3E3';
    private const TLO_STATUS = 'FFE2EFDA';
    private const TEKST_STATUS = 'FF375623';
    private const TLO_NAGLOWKA = 'FFF2F2F2';

    private array $shiftStatuses;
    /** Kody, które faktycznie wystąpiły w tym miesiącu — do legendy. */
    private array $uzyteKody = [];
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
            ->addDaysHeaders($date)
            ->addWorkersShifts($shifts)
            ->addLegend()
            ->addGeneralFormatting($date);

        return $this;
    }

    /**
     * Każdy eksport pisze do własnego pliku. Wcześniej wszystkie szły do
     * jednego `kcp.xlsx`, więc dwie osoby pobierające KCP w tej samej chwili
     * mogły dostać nie swoją budowę.
     */
    public function export(?string $filename = ''): string
    {
        $katalog = storage_path('app/export');

        if (! File::exists($katalog)) {
            File::makeDirectory($katalog, 0755, true);
        }

        $path = $katalog . '/kcp-' . Str::random(16) . '.xlsx';
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
        // Tytuł na całą szerokość arkusza. Wcześniej siedział w wąskim
        // scaleniu nad kolumnami dni i łamał się na trzy linijki.
        $this
            ->activeWorksheet
            ->setCellValue('A3', 'ZESTAWIENIE PRZEPRACOWANYCH GODZIN — '
                . mb_strtoupper($date->locale('pl_PL')->monthName . ' ' . $date->year));

        $this->activeWorksheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
        $this->activeWorksheet->getStyle('B5')->getFont()->setBold(true);

        $this
            ->activeWorksheet
            ->setCellValue('A' . $titlesRow, 'LP');

        $this
            ->activeWorksheet
            ->setCellValue('B' . $titlesRow, 'Nazwisko i imię');

        $this
            ->activeWorksheet
            ->setCellValue('C' . $titlesRow, 'Rodzaj');

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
        // Pogrubienie tylko tam, gdzie coś znaczy — wcześniej cały arkusz był
        // bold, więc nagłówki niczym się nie wyróżniały.
        $this->spreadsheet->getDefaultStyle()->getFont()->setSize(10);

        $this->activeWorksheet = $this->spreadsheet->getActiveSheet();
    }

    private function addDaysHeaders(Carbon $date): static
    {
        $daysHeadersGenerator = $this->rowsGenerator->cellCoordinatesGenerator(68);

        $pierwszyDzien = $date->copy()->startOfMonth();

        // Liczba dni z miesiąca, nie z pierwszego pracownika: przy budowie bez
        // nikogo reset() dawał false i count(false) kończył eksport błędem 500.
        // Przy obsadzie wynik ten sam — każdy ma pozycję na każdy dzień miesiąca.
        $daysRow = 7;
        foreach (range(1, $date->daysInMonth) as $key => $day) {
            $firstCell = $daysHeadersGenerator->current();
            $daysHeadersGenerator->next();
            $secondCell = $daysHeadersGenerator->current();

            $firstCellCoords = $firstCell . $daysRow;
            $secondCellCoords = $secondCell . $daysRow;
            $value = $key + 1;

            // Sam numer dnia nie mówi, czy to sobota — a od tego zależy, czy
            // pusta kratka jest brakiem, czy wolnym. Skrót dnia jak na ekranie.
            $dzien = $pierwszyDzien->copy()->addDays($key);

            $this->activeWorksheet->setCellValue(
                $firstCellCoords,
                $value . "\n" . $dzien->locale('pl_PL')->shortDayName
            );
            $this->activeWorksheet->mergeCells($firstCellCoords . ':' . $secondCellCoords);

            $this->activeWorksheet
                ->getStyle($firstCellCoords . ':' . $secondCellCoords)
                ->applyFromArray($this->borderStyleThin)
                ->getAlignment()
                ->setHorizontal('center')
                ->setVertical('center')
                ->setWrapText(true);

            $tlo = self::TLO_NAGLOWKA;

            if ($dzien->isSunday()) {
                $tlo = self::TLO_NIEDZIELA;
            } elseif ($dzien->isSaturday()) {
                $tlo = self::TLO_SOBOTA;
            }

            $this->activeWorksheet
                ->getStyle($firstCellCoords . ':' . $secondCellCoords)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($tlo);

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

        $this->activeWorksheet
            ->getStyle('A' . $daysRow . ':' . $sumCell . $daysRow)
            ->getFont()->setBold(true);

        $this->activeWorksheet
            ->getStyle($sumCell . $daysRow)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TLO_NAGLOWKA);

        $this->activeWorksheet
            ->getStyle('A' . $daysRow . ':C' . $daysRow)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TLO_NAGLOWKA);

        $this->activeWorksheet->getRowDimension($daysRow)->setRowHeight(28);

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
            $this->activeWorksheet->getStyle('B' . $workHoursRow)->getFont()->setBold(true);
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
                    $zakresy = [
                        $cellFrom . ':' . $cellTo,
                        $cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow,
                        $cellCoordsFrom . $paidFor . ':' . $cellCoordsTo . $paidFor,
                    ];

                    foreach ($zakresy as $zakres) {
                        $styl = $this->activeWorksheet->getStyle($zakres);
                        $styl->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB(self::TLO_STATUS);
                        $styl->getFont()->getColor()->setARGB(self::TEKST_STATUS);
                    }

                    // set shift status code e.g. UW,OG
                    $foundShifts = array_filter($this->shiftStatuses, static fn($shiftStatus) => $shiftStatus->id === $shift->status);
                    $code = reset($foundShifts)->code;
                    $this->activeWorksheet->setCellValue($cellCoordsFrom . $paidFor, $code);
                    $this->activeWorksheet->getStyle($cellCoordsFrom . $paidFor)->getFont()->setBold(true);
                    $this->uzyteKody[$code] = reset($foundShifts)->title ?? '';

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

                if ($shift->isSaturday() || $shift->blockedType === 'feast') {
                    $this->activeWorksheet
                        ->getStyle($cellFrom . ':' . $cellTo)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(self::TLO_SOBOTA);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(self::TLO_SOBOTA);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $paidFor . ':' . $cellCoordsTo . $paidFor)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(self::TLO_SOBOTA);
                }

                if ($shift->isSunday()) {
                    $this->activeWorksheet
                        ->getStyle($cellFrom . ':' . $cellTo)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(self::TLO_NIEDZIELA);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $workingHoursRow . ':' . $cellCoordsTo . $workingHoursRow)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(self::TLO_NIEDZIELA);

                    $this->activeWorksheet
                        ->getStyle($cellCoordsFrom . $paidFor . ':' . $cellCoordsTo . $paidFor)
                        ->applyFromArray($this->borderStyleThin)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(self::TLO_NIEDZIELA);
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
                    $shiftInMinutes = (int) Carbon::parse($shift->work)->diffInMinutes(Carbon::now()->startOfDay(), true);
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
                ->getFont()->setBold(true);

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

    /**
     * Arkusz operuje skrótami (UW, OG, ZL), więc rozwijamy te, które w tym
     * miesiącu padły. Bez tego trzeba było pytać biuro, co znaczy kod.
     */
    private function addLegend(): static
    {
        $wiersz = $this->activeWorksheet->getHighestRow() + 2;

        $this->activeWorksheet->setCellValue('B' . $wiersz, 'Oznaczenia:');
        $this->activeWorksheet->getStyle('B' . $wiersz)->getFont()->setBold(true);

        $this->activeWorksheet->setCellValue('C' . $wiersz, 'sobota');
        $this->activeWorksheet->getStyle('C' . $wiersz)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TLO_SOBOTA);

        $this->activeWorksheet->setCellValue('D' . $wiersz, 'niedziela / święto');
        $this->activeWorksheet->mergeCells('D' . $wiersz . ':H' . $wiersz);
        $this->activeWorksheet->getStyle('D' . $wiersz . ':H' . $wiersz)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TLO_NIEDZIELA);

        ksort($this->uzyteKody);

        foreach ($this->uzyteKody as $kod => $nazwa) {
            $wiersz++;
            $this->activeWorksheet->setCellValue('B' . $wiersz, $kod);
            $this->activeWorksheet->getStyle('B' . $wiersz)->getFont()->setBold(true);
            $this->activeWorksheet->getStyle('B' . $wiersz)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TLO_STATUS);
            $this->activeWorksheet->setCellValue('C' . $wiersz, $nazwa);
            $this->activeWorksheet->mergeCells('C' . $wiersz . ':F' . $wiersz);
            $this->activeWorksheet->getStyle('C' . $wiersz)->getAlignment()->setHorizontal('left');
        }

        return $this;
    }

    private function addGeneralFormatting(Carbon $date): static
    {
        $dni = (int) $date->copy()->endOfMonth()->day;
        // Kolumny dni zaczynają się od D i zajmują po dwie; na końcu suma.
        $ostatniaKolumna = Coordinate::stringFromColumnIndex(4 + 2 * $dni);

        $this->activeWorksheet->mergeCells('A3:' . $ostatniaKolumna . '3');
        $this->activeWorksheet->getStyle('A3')->getAlignment()->setHorizontal('center');

        // Nazwisko i podpis wiersza mają zostać na ekranie przy przewijaniu
        // w bok, a nagłówek dni przy przewijaniu w dół.
        $this->activeWorksheet->freezePane('D8');

        $this->activeWorksheet->getColumnDimension('A')->setWidth(5);
        $this->activeWorksheet->getColumnDimension('B')->setWidth(26);
        $this->activeWorksheet->getColumnDimension('C')->setWidth(17);

        foreach (range(4, 4 + 2 * $dni) as $kolumna) {
            $this->activeWorksheet
                ->getColumnDimension(Coordinate::stringFromColumnIndex($kolumna))
                ->setWidth(4.5);
        }

        $this->activeWorksheet->getStyle('A:' . $ostatniaKolumna)
            ->getAlignment()
            ->setHorizontal('center');

        $this->activeWorksheet->getStyle('B8:B' . $this->activeWorksheet->getHighestRow())
            ->getAlignment()
            ->setHorizontal('left');

        // Wydruk: cały miesiąc na szerokość jednej strony, z nagłówkiem dni
        // powtórzonym na każdej kartce.
        $wydruk = $this->activeWorksheet->getPageSetup();
        $wydruk->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $wydruk->setPaperSize(PageSetup::PAPERSIZE_A4);
        $wydruk->setFitToWidth(1);
        $wydruk->setFitToHeight(0);
        $wydruk->setRowsToRepeatAtTopByStartAndEnd(7, 7);
        $this->activeWorksheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.3)->setRight(0.3);

        return $this;
    }
}
