<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PolskaKolacjaDlaUzytkownikow extends Migration
{
    /**
     * Dopisanie tabeli users do polskiej kolacji.
     *
     * Przy poprzedniej poprawce (PolskaKolacjaDlaNazw) objęliśmy kartoteki
     * pracowników, budowy i słowniki, ale lista użytkowników została z
     * utf8mb4_unicode_ci — a sortuje się po nazwisku tak samo.
     *
     * @return void
     */
    private const KOLUMNY = [
        ['users', 'last_name', 'VARCHAR(25)', 'NOT NULL'],
        ['users', 'first_name', 'VARCHAR(25)', 'NOT NULL'],
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
                $tabela, $kolumna, $typ, $kolacja, $null
            ));
        }
    }
}
