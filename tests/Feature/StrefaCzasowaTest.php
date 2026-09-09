<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Aplikacja i baza muszą chodzić na tym samym czasie.
 *
 * Przy 'UTC' w konfiguracji PHP był o dwie godziny do tyłu względem bazy
 * (MySQL chodzi na czasie systemowym serwera): znaczniki zapisywały się
 * o 2 h wcześniej niż wskazywał zegar, a między 22:00 a północą czasu
 * polskiego `now()->toDateString()` zwracał jeszcze poprzedni dzień —
 * przekłamując wszystko liczone "na dziś".
 */
class StrefaCzasowaTest extends TestCase
{
    public function test_aplikacja_chodzi_na_czasie_polskim(): void
    {
        $this->assertSame('Europe/Warsaw', config('app.timezone'));
        $this->assertSame('Europe/Warsaw', date_default_timezone_get());
    }

    public function test_php_i_baza_pokazuja_ten_sam_dzien(): void
    {
        $this->assertSame(
            now()->toDateString(),
            DB::selectOne('SELECT CURDATE() as d')->d,
            'Rozjazd dnia między PHP a bazą psuje wszystko liczone "na dziś".'
        );
    }

    public function test_php_i_baza_pokazuja_ten_sam_czas(): void
    {
        $roznica = abs(now()->timestamp - strtotime(DB::selectOne('SELECT NOW() as t')->t));

        $this->assertLessThan(60, $roznica, "PHP i baza rozjeżdżają się o {$roznica} s.");
    }
}
