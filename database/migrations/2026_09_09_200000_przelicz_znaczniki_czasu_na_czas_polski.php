<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Znaczniki czasu zapisane, gdy aplikacja chodziła na UTC, przeliczone
 * na czas polski.
 *
 * Do 09.09.2026 config/app.php miał 'UTC', więc wszystko, co zapisywał PHP,
 * lądowało w bazie o dwie godziny wcześniej, niż wskazywał zegar (zimą
 * o godzinę). Logowanie o 17:41 zapisało się jako 15:41.
 *
 * Przeliczamy tylko wartości sprzed wdrożenia poprawki — nowsze są już
 * w czasie polskim. Offset liczymy dla każdej wartości osobno, bo zależy
 * od tego, czy wypadła w czasie letnim, czy zimowym.
 *
 * Nie ruszamy kolumn, w które użytkownik wpisuje godzinę z ręki: work_day,
 * work_from i work_to w Karcie Czasu Pracy oraz users.login_time. To zegar
 * ścienny, a nie znacznik zdarzenia — przeliczenie zrobiłoby z 07:00
 * godzinę 09:00 i zniszczyłoby rozliczenia godzin.
 */
class PrzeliczZnacznikiCzasuNaCzasPolski extends Migration
{
    /** Wartości zapisane przed tą chwilą są w UTC (zapis: czas UTC). */
    private const GRANICA_UTC = '2026-09-09 18:35:00';

    /** Ta sama chwila po przeliczeniu — do cofnięcia zmiany. */
    private const GRANICA_PL = '2026-09-09 20:35:00';

    /** @var array<string, string[]> kolumny wpisywane ręcznie — nie dotykamy */
    private const POMIJANE = [
        'building_time_sheets' => ['work_day', 'work_from', 'work_to'],
        'users' => ['login_time'],
    ];

    public function up(): void
    {
        $this->przelicz('UTC', 'Europe/Warsaw', self::GRANICA_UTC);
    }

    public function down(): void
    {
        $this->przelicz('Europe/Warsaw', 'UTC', self::GRANICA_PL);
    }

    private function przelicz(string $zeStrefy, string $doStrefy, string $granica): void
    {
        $zrodlo = new DateTimeZone($zeStrefy);
        $cel = new DateTimeZone($doStrefy);
        $zmienionych = 0;

        foreach ($this->kolumnyCzasu() as [$tabela, $kolumna]) {
            DB::table($tabela)
                ->select('id', $kolumna)
                ->whereNotNull($kolumna)
                ->where($kolumna, '<', $granica)
                ->orderBy('id')
                ->chunk(500, function ($wiersze) use ($tabela, $kolumna, $zrodlo, $cel, &$zmienionych) {
                    foreach ($wiersze as $w) {
                        $czas = new DateTime((string) $w->$kolumna, $zrodlo);
                        $czas->setTimezone($cel);

                        DB::table($tabela)->where('id', $w->id)
                            ->update([$kolumna => $czas->format('Y-m-d H:i:s')]);
                        $zmienionych++;
                    }
                });
        }

        echo "  przeliczono wartości: {$zmienionych}\n";
    }

    /**
     * Kolumny datetime/timestamp w tabelach, które mają klucz `id` — bez niego
     * nie da się bezpiecznie zaktualizować pojedynczego wiersza.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function kolumnyCzasu(): array
    {
        $baza = DB::getDatabaseName();

        $kolumny = DB::select(
            "SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND DATA_TYPE IN ('datetime','timestamp')
             ORDER BY TABLE_NAME, COLUMN_NAME",
            [$baza]
        );

        $zId = collect(DB::select(
            "SELECT DISTINCT TABLE_NAME t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'id'",
            [$baza]
        ))->pluck('t')->all();

        $wynik = [];

        foreach ($kolumny as $k) {
            if (in_array($k->c, self::POMIJANE[$k->t] ?? [], true)) {
                continue;
            }

            if (! in_array($k->t, $zId, true)) {
                continue;
            }

            $wynik[] = [$k->t, $k->c];
        }

        return $wynik;
    }
}
