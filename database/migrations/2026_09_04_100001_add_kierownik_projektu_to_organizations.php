<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKierownikProjektuToOrganizations extends Migration
{
    /**
     * Osoba odpowiedzialna za realizację kontraktu — wpisywana ręcznie.
     * To ktoś inny niż kierownik budowy i nie ma go w bazie pracowników,
     * dlatego zwykły tekst, bez relacji.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('kierownik_projektu', 100)->nullable()->after('zaklad');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('kierownik_projektu');
        });
    }
}
