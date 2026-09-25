<?php

namespace App\Services;

use App\Services\Date\ExcelTimeFormatter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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

    public function generate(iterable $shifts, CarbonPeriod $date, ?iterable $bezWpisow = null, array $budowyBezKcp = [])
    {
        $title = $date->first()?->locale('pl_PL')->isoFormat('MMMM YYYY');
        if ($title) {
            $this->activeWorksheet->setTitle('Dni miesiąca');
        }

        $this
            ->addMainHeaders($date)
            ->addDaysHeader($date)
            ->addData($shifts, $date)
            ->addGeneralFormatting()
            ->addSummarySheet($shifts, $date, $bezWpisow ?? [], $budowyBezKcp);

        return $this;
    }

    private function addMainHeaders(CarbonPeriod $period): self
    {
        $this
            ->activeWorksheet
            // mb_, bo strtoupper zostawiał ogonki małe: "WRZESIEń".
            ->setCellValue('A1', mb_strtoupper(
                $period->first()?->locale('pl_PL')->monthName . ' ' . $period->first()?->year
            ));

        $this
            ->activeWorksheet
            ->mergeCells('A1:AK1');

        $this
            ->activeWorksheet
            ->setCellValue('A2', 'Suma godzin');

        $this
            ->activeWorksheet
            ->setCellValue('B2', 'Nazwisko');

        $this
            ->activeWorksheet
            ->setCellValue('C2', 'Imię');

        return $this;
    }

    /**
     * Każdy eksport pisze do własnego pliku — wcześniej wszystkie szły do
     * jednego `general_report.xlsx` i dwie osoby pobierające raport naraz
     * mogły dostać cudzy plik.
     */
    public function export(?string $filename = ''): string
    {
        $path = storage_path('app/export/');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $path .= 'raport-' . Str::random(16) . '.xlsx';
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
        $days = array_map(static fn(Carbon $carbon) => $carbon->day . ' ' . $carbon->locale('pl_PL')->shortDayName, $period->toArray());

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

    /**
     * Kolor tła kratki dnia wg zawartości (jak w firmowym pliku):
     * żółty = urlop (UW/UO/UB/UŻ), zielony = U.reh, szary = dzień bez wpisu.
     * Numer budowy i pozostałe kody (ZL, OG) zostają na tle wiersza (null).
     */
    private function kolorKratki(string $wartosc): ?string
    {
        $v = trim($wartosc);

        if ($v === '') {
            return 'd9d9d9';
        }
        if (stripos($v, 'reh') !== false) {
            return 'a9d08e';
        }

        // Same kody urlopu (bez numerów budów) → żółty.
        $tokeny = explode('/', $v);
        $urlop = collect($tokeny)->every(
            static fn (string $t) => $t !== '' && ! ctype_digit($t) && stripos($t, 'reh') === false && strncasecmp($t, 'U', 1) === 0
        );

        return $urlop ? 'ffff00' : null;
    }

    private function addData(iterable $shifts, CarbonPeriod $period): self
    {
        $startingRowId = 3;

        $period->count();
        /** @var Collection $shift */
        foreach ($shifts as $shift) {
            $sumHours = 0;
            /** @var [ 2 => 386 ] $dayToCode */
            // W dzień pracy kratka pokazuje NUMER BUDOWY (jak w firmowym pliku),
            // nie godziny — te sumują się z boku w „Suma godzin". Nieobecność
            // pokazuje swój kod. Dwie budowy tego samego dnia: numery po ukośniku.
            $perDay = array_fill(0, $period->count(), []);

            foreach ($shift as $item) {
                $dzien = Carbon::create($item->work_day)->day - 1;

                if ($item->code) {
                    $token = $item->code;
                } elseif (!empty($item->effective_work_time) && (int)str_replace(':', '', $item->effective_work_time) > 0) {
                    $token = (string)$item->numerBud;
                    $sumHours += (float)str_replace(',', '.', (string)ExcelTimeFormatter::dateToInteger($item->effective_work_time));
                } else {
                    continue;
                }

                if ($token !== '' && !in_array($token, $perDay[$dzien], true)) {
                    $perDay[$dzien][] = $token;
                }
            }

            $rowForWorker = array_map(static fn (array $tokeny) => implode('/', $tokeny), $perDay);

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
            // Liczba, nie tekst: dotąd kolumna "Suma godzin" była napisem
            // z przecinkiem, więc Excel nie umiał jej zsumować ani posortować.
            $this->activeWorksheet->setCellValue('A' . $startingRowId, round($sumHours, 2));

            $this
                ->activeWorksheet
                ->getStyle('A' . $startingRowId . ':' . 'AK' . $startingRowId)
                ->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => $startingRowId % 2 !== 0 ? 'bdd6ee' : 'deeaf6']
                    ]
                ]);

            // Kolor kratki po zawartości: urlop żółty, U.reh zielony, pusty dzień
            // szary; numer budowy zostaje na tle wiersza. Kolumny dni od D (=4).
            foreach ($rowForWorker as $i => $wartosc) {
                $kolor = $this->kolorKratki($wartosc);
                if ($kolor === null) {
                    continue;
                }
                $kol = Coordinate::stringFromColumnIndex(4 + $i);
                $this->activeWorksheet->getStyle($kol . $startingRowId)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($kolor);
            }

            $startingRowId++;
        }

        return $this;
    }


    /**
     * Zakładka "Podsumowanie": jeden wiersz na pracownika, bez siatki dni.
     *
     * Dotąd raport dawał samą sumę godzin, więc urlopy, zwolnienia i odbiory
     * godzin trzeba było zliczać z kratek. Tu każdy rodzaj ma własną kolumnę,
     * a budowa mówi, gdzie ten miesiąc został przepracowany.
     */
    private function addSummarySheet(iterable $shifts, CarbonPeriod $period, iterable $bezWpisow, array $budowyBezKcp = []): self
    {
        $arkusz = $this->spreadsheet->createSheet();
        $arkusz->setTitle('Podsumowanie');

        // Kolumny rodzajów tworzymy tylko dla tych, które w tym miesiącu padły.
        $kody = [];

        foreach ($shifts as $wiersze) {
            foreach ($wiersze as $wpis) {
                if ($wpis->code) {
                    $kody[$wpis->code] = $wpis->status_nazwa ?? '';
                }
            }
        }

        ksort($kody);

        $naglowki = array_merge(
            ['Lp.', 'Nazwisko', 'Imię', 'Budowa', 'Dni pracy', 'Godziny'],
            array_keys($kody),
            ['Uwagi']
        );

        $arkusz->setCellValue('A1', 'PODSUMOWANIE MIESIĄCA — '
            . mb_strtoupper($period->first()->locale('pl_PL')->monthName . ' ' . $period->first()->year));
        $arkusz->mergeCells('A1:' . Coordinate::stringFromColumnIndex(count($naglowki)) . '1');
        $arkusz->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $arkusz->getStyle('A1')->getAlignment()->setHorizontal('center');

        $arkusz->fromArray([$naglowki], null, 'A3');
        $arkusz->getStyle('A3:' . Coordinate::stringFromColumnIndex(count($naglowki)) . '3')
            ->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '5b9bd5']],
            ]);

        $wiersz = 4;
        $lp = 1;
        $sumaGodzin = 0.0;
        $sumaDni = 0;
        $sumyKodow = array_fill_keys(array_keys($kody), 0);

        foreach ($shifts as $wpisy) {
            $godziny = 0.0;
            $dniPracy = 0;
            $liczbaKodow = array_fill_keys(array_keys($kody), 0);
            $budowy = [];

            foreach ($wpisy as $wpis) {
                if ($wpis->nazwaBud) {
                    $budowy[$wpis->nazwaBud] = true;
                }

                if ($wpis->code) {
                    $liczbaKodow[$wpis->code] = ($liczbaKodow[$wpis->code] ?? 0) + 1;
                    continue;
                }

                $wGodzinach = $this->naGodziny($wpis->effective_work_time);

                if ($wGodzinach > 0) {
                    $godziny += $wGodzinach;
                    $dniPracy++;
                }
            }

            $pierwszy = $wpisy->first();

            $dane = array_merge(
                [$lp, $pierwszy->last_name, $pierwszy->first_name, implode(', ', array_keys($budowy)), $dniPracy, $godziny],
                array_values($liczbaKodow),
                ['']
            );

            $arkusz->fromArray([$dane], null, 'A' . $wiersz);

            $sumaGodzin += $godziny;
            $sumaDni += $dniPracy;

            foreach ($liczbaKodow as $kod => $ile) {
                $sumyKodow[$kod] += $ile;
            }

            $this->pasek($arkusz, $wiersz, count($naglowki));
            $wiersz++;
            $lp++;
        }

        // Kto był na budowie, a nie ma ani jednego wpisu — inaczej taka osoba
        // w ogóle nie pojawia się w raporcie i łatwo o niej zapomnieć.
        foreach ($bezWpisow as $osoba) {
            $dane = array_merge(
                [$lp, $osoba->last_name, $osoba->first_name, $osoba->nazwaBud, 0, 0],
                array_fill(0, count($kody), 0),
                ['brak wpisów w KCP']
            );

            $arkusz->fromArray([$dane], null, 'A' . $wiersz);
            $arkusz->getStyle('A' . $wiersz . ':' . Coordinate::stringFromColumnIndex(count($naglowki)) . $wiersz)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFBE3E3');

            $wiersz++;
            $lp++;
        }

        // Wiersz sumy
        $razem = array_merge(
            ['', 'RAZEM', '', '', $sumaDni, $sumaGodzin],
            array_values($sumyKodow),
            ['']
        );
        $arkusz->fromArray([$razem], null, 'A' . $wiersz);
        $arkusz->getStyle('A' . $wiersz . ':' . Coordinate::stringFromColumnIndex(count($naglowki)) . $wiersz)
            ->getFont()->setBold(true);
        $arkusz->getStyle('A' . $wiersz . ':' . Coordinate::stringFromColumnIndex(count($naglowki)) . $wiersz)
            ->applyFromArray([
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => Color::COLOR_BLACK]]],
            ]);

        // Budowy, na których w tym miesiącu nikt nic nie wypełnił — jedna
        // notka zamiast kilkudziesięciu wierszy "brak wpisów".
        if ($budowyBezKcp !== []) {
            $wiersz += 2;
            $arkusz->setCellValue('B' . $wiersz, 'Budowy bez żadnego wpisu w tym miesiącu:');
            $arkusz->getStyle('B' . $wiersz)->getFont()->setBold(true);
            $arkusz->setCellValue('C' . $wiersz, implode(', ', $budowyBezKcp));
            $arkusz->mergeCells('C' . $wiersz . ':' . Coordinate::stringFromColumnIndex(max(6, count($naglowki))) . $wiersz);
            $arkusz->getStyle('C' . $wiersz)->getAlignment()->setHorizontal('left')->setWrapText(true);
        }

        // Legenda skrótów
        $wiersz += 2;
        $arkusz->setCellValue('B' . $wiersz, 'Oznaczenia:');
        $arkusz->getStyle('B' . $wiersz)->getFont()->setBold(true);

        foreach ($kody as $kod => $nazwa) {
            $wiersz++;
            $arkusz->setCellValue('B' . $wiersz, $kod);
            $arkusz->getStyle('B' . $wiersz)->getFont()->setBold(true);
            $arkusz->setCellValue('C' . $wiersz, $nazwa);
            $arkusz->mergeCells('C' . $wiersz . ':E' . $wiersz);
            $arkusz->getStyle('C' . $wiersz)->getAlignment()->setHorizontal('left');
        }

        $arkusz->freezePane('A4');
        $arkusz->getColumnDimension('A')->setWidth(5);
        $arkusz->getColumnDimension('B')->setWidth(20);
        $arkusz->getColumnDimension('C')->setWidth(16);
        $arkusz->getColumnDimension('D')->setWidth(30);

        foreach (range(5, count($naglowki)) as $kolumna) {
            $arkusz->getColumnDimension(Coordinate::stringFromColumnIndex($kolumna))->setWidth(11);
        }

        $arkusz->getStyle('E4:' . Coordinate::stringFromColumnIndex(count($naglowki) - 1) . $wiersz)
            ->getAlignment()->setHorizontal('center');

        // Raport otwiera się na podsumowaniu, siatka dni zostaje do sprawdzania.
        $this->spreadsheet->setActiveSheetIndexByName('Podsumowanie');

        return $this;
    }

    /** Naprzemienne paski, tak samo jak w siatce dni. */
    private function pasek(Worksheet $arkusz, int $wiersz, int $kolumn): void
    {
        $arkusz->getStyle('A' . $wiersz . ':' . Coordinate::stringFromColumnIndex($kolumn) . $wiersz)
            ->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $wiersz % 2 !== 0 ? 'bdd6ee' : 'deeaf6'],
                ],
            ]);
    }

    /**
     * "09:30" -> 9.5. Wcześniejsze przeliczenie zaokrąglało wszystko powyżej
     * pół godziny w dół, więc 45 minut liczyło się jako zero.
     */
    private function naGodziny(?string $czas): float
    {
        if (! $czas || ! preg_match('/^(\d{1,2}):(\d{1,2})$/', trim($czas), $czesci)) {
            return 0.0;
        }

        return (int) $czesci[1] + ((int) $czesci[2] / 60);
    }

    private function addGeneralFormatting(): self
    {
        // Było range('A', 'AK'): PHP do 8.2 bierze z 'AK' tylko pierwszą literę,
        // więc autoszerokość zawsze dostawała sama kolumna A. PHP 8.3+ zgłasza
        // to jako błąd (500 na eksporcie). Zostawiamy dotychczasowy wygląd pliku.
        $this->activeWorksheet->getColumnDimension('A')->setAutoSize(true);

        $this
            ->activeWorksheet
            ->getStyle('A:AK')
            ->getAlignment()
            ->setHorizontal('center');

        return $this;
    }
}
