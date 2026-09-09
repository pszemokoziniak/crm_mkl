<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRolaBudowyToFunkcjasTable extends Migration
{
    /**
     * W której kolumnie listy budów pokazać osobę z tym stanowiskiem.
     *
     * Kolumny liczyły się zapytaniem `where funkcja_id = 1` i `= 6`, więc
     * z 43 osób na stanowiskach kierowniczych widać było 19 — reszta znikała
     * bez śladu. Znacznik `kierownictwo` mówi tylko, czy stanowisko należy do
     * kierownictwa; nie mówi, do której kolumny.
     *
     * Przypisanie siedzi w słowniku, nie w kodzie: kolejne stanowisko biuro
     * ustawi samo w Ustawieniach, bez wdrożenia.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('funkcjas', function (Blueprint $table) {
            $table->string('rola_budowy', 20)->nullable()->after('kierownictwo')->index();
        });

        // Podział ustalony przez biuro (zgłoszenie Tomasza Budy).
        $przypisania = [
            'kierownik' => [
                'Kierownik Budowy',
                'Kierownik - budowy GW Polska',
            ],
            'inzynier' => [
                'Inżynier Budowy',
                'Koordynator ds. Realizacji',
                'Specjalista BHP',
                'Inżynier Spawalnik',
            ],
            'kierownik_projektu' => [
                'Kierownik Projektu',
            ],
        ];

        foreach ($przypisania as $rola => $stanowiska) {
            DB::table('funkcjas')->whereIn('name', $stanowiska)->update(['rola_budowy' => $rola]);
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('funkcjas', function (Blueprint $table) {
            $table->dropColumn('rola_budowy');
        });
    }
}
