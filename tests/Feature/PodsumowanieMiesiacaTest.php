<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\BuildsExcelExporter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Plik „Podsumowanie miesiąca", zakładka „Dni miesiąca": w dzień pracy kratka
 * pokazuje numer budowy (nie godziny), nieobecność swój kod, dwie budowy tego
 * samego dnia po ukośniku; urlop żółty, dzień bez wpisu szary.
 */
class PodsumowanieMiesiacaTest extends TestCase
{
    private function wpis(array $a): object
    {
        return (object) array_merge([
            'contact_id' => 1, 'work_day' => '2026-10-01', 'numerBud' => null, 'nazwaBud' => null,
            'code' => null, 'status_nazwa' => null, 'first_name' => 'Jan', 'last_name' => 'Kowalski',
            'effective_work_time' => null,
        ], $a);
    }

    public function test_dni_miesiaca_ma_numer_budowy_ukosnik_i_kolory(): void
    {
        $period = CarbonPeriod::create(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $shifts = new Collection([
            1 => new Collection([
                // dzień 1: praca na 504
                $this->wpis(['work_day' => '2026-10-01', 'numerBud' => '504', 'nazwaBud' => 'Bud 504', 'effective_work_time' => '08:00']),
                // dzień 2: dwie budowy → 504/507
                $this->wpis(['work_day' => '2026-10-02', 'numerBud' => '504', 'nazwaBud' => 'Bud 504', 'effective_work_time' => '04:00']),
                $this->wpis(['work_day' => '2026-10-02', 'numerBud' => '507', 'nazwaBud' => 'Bud 507', 'effective_work_time' => '04:00']),
                // dzień 3: urlop
                $this->wpis(['work_day' => '2026-10-03', 'code' => 'UW', 'status_nazwa' => 'Urlop wypoczynkowy']),
                // dzień 5: U.reh
                $this->wpis(['work_day' => '2026-10-05', 'code' => 'U.reh', 'status_nazwa' => 'Rehabilitacja']),
                // dzień 4: brak wpisu (pusty)
            ]),
        ]);

        $plik = (new BuildsExcelExporter())->generate($shifts, $period, [], [])->export();
        $arkusz = IOFactory::load($plik)->getSheetByName('Dni miesiąca');

        // Kolumny dni od D (=4); wiersz pierwszego pracownika = 3.
        $this->assertSame('504', (string) $arkusz->getCell('D3')->getValue());
        $this->assertSame('504/507', (string) $arkusz->getCell('E3')->getValue());
        $this->assertSame('UW', (string) $arkusz->getCell('F3')->getValue());
        $this->assertSame('', (string) $arkusz->getCell('G3')->getValue()); // dzień 4 pusty

        // Kolory: urlop żółty, U.reh zielony, pusty szary, numer budowy nie żółty/szary.
        $this->assertStringContainsStringIgnoringCase('ffff00', $arkusz->getStyle('F3')->getFill()->getStartColor()->getARGB());
        $this->assertStringContainsStringIgnoringCase('a9d08e', $arkusz->getStyle('H3')->getFill()->getStartColor()->getARGB());
        $this->assertStringContainsStringIgnoringCase('d9d9d9', $arkusz->getStyle('G3')->getFill()->getStartColor()->getARGB());
        $barwaPracy = strtolower($arkusz->getStyle('D3')->getFill()->getStartColor()->getARGB());
        $this->assertStringNotContainsString('ffff00', $barwaPracy);
        $this->assertStringNotContainsString('d9d9d9', $barwaPracy);

        @unlink($plik);
    }
}
