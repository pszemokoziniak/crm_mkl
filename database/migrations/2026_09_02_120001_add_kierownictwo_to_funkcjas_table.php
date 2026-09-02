<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddKierownictwoToFunkcjasTable extends Migration
{
    /**
     * Które stanowiska mogą trafić do kierownictwa budowy. Wcześniej lista
     * była zaszyta w kodzie (kierownik + inżynier), więc dołożenie stanowiska
     * wymagało wdrożenia. Teraz to zwykły znacznik przy funkcji.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('funkcjas', function (Blueprint $table) {
            $table->boolean('kierownictwo')->default(false)->after('name')->index();
        });

        // Zakres ustalony przez biuro (zgłoszenie Tomasza Budy).
        $stanowiska = [
            'Kierownik Budowy',
            'Inżynier Budowy',
            'Kierownik Projektu',
            'Koordynator ds. Realizacji',
            'Specjalista BHP',
            'Inżynier Spawalnik',
            'Kierownik - budowy GW Polska',
        ];

        foreach ($stanowiska as $nazwa) {
            DB::table('funkcjas')->where('name', $nazwa)->update(['kierownictwo' => true]);
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('funkcjas', function (Blueprint $table) {
            $table->dropColumn('kierownictwo');
        });
    }
}
