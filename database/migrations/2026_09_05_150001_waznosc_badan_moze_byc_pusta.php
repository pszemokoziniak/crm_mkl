<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class WaznoscBadanMozeBycPusta extends Migration
{
    /**
     * Data badań technicznych bywa nieznana, a kolumna nie przyjmowała pustej
     * wartości — dodanie sprzętu bez daty kończyło się błędem. Stąd w bazie
     * daty-zastępniki (rok 9999 albo -0001) wpisywane, żeby formularz przeszedł.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE narzedzias MODIFY waznosc_badan DATE NULL');

        // Zastępniki zamieniamy na brak daty — i tak nie niosły informacji,
        // a magazyn pokazywał je jako "brak daty".
        DB::table('narzedzias')
            ->where('waznosc_badan', '<', '2000-01-01')
            ->orWhere('waznosc_badan', '>', '2100-01-01')
            ->update(['waznosc_badan' => null]);
    }

    /**
     * @return void
     */
    public function down()
    {
        DB::table('narzedzias')->whereNull('waznosc_badan')->update(['waznosc_badan' => '9999-12-31']);

        DB::statement('ALTER TABLE narzedzias MODIFY waznosc_badan DATE NOT NULL');
    }
}
