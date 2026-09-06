<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PolskaKolacjaDlaNazw extends Migration
{
    /**
     * Nazwiska i nazwy sortują się polskim alfabetem.
     *
     * Kolumny miały utf8mb4_unicode_ci, gdzie Ś jest tym samym co S — przez to
     * "Śledź" lądował między "Skoczylas" a "Sobiczewski", a "Świetlicki" między
     * "Sujka" a "Szafrański". Poprawianie tego zapytanie po zapytaniu nie działa:
     * miejsc sortujących po nazwisku jest kilkanaście i łatwo o kolejne pominąć.
     * Ustawiamy więc kolację na samych kolumnach, żeby każde ORDER BY — także
     * to napisane w przyszłości — układało tak samo.
     *
     * @return void
     */
    private const KOLUMNY = [
        ['contacts', 'last_name', 'VARCHAR(25)', 'NOT NULL'],
        ['contacts', 'first_name', 'VARCHAR(25)', 'NOT NULL'],
        ['organizations', 'nazwaBud', 'VARCHAR(100)', 'NULL'],
        ['funkcjas', 'name', 'VARCHAR(50)', 'NOT NULL'],
        ['narzedzia_typs', 'name', 'VARCHAR(100)', 'NOT NULL'],
        ['narzedzia_typs', 'kategoria', 'VARCHAR(100)', 'NULL'],
    ];

    public function up()
    {
        $this->ustawKolacje('utf8mb4_polish_ci');
    }

    /**
     * @return void
     */
    public function down()
    {
        $this->ustawKolacje('utf8mb4_unicode_ci');
    }

    private function ustawKolacje(string $kolacja): void
    {
        foreach (self::KOLUMNY as [$tabela, $kolumna, $typ, $null]) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` %s CHARACTER SET utf8mb4 COLLATE %s %s',
                $tabela,
                $kolumna,
                $typ,
                $kolacja,
                $null
            ));
        }
    }
}
