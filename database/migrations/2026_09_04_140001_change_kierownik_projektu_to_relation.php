<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeKierownikProjektuToRelation extends Migration
{
    /**
     * Kierownik projektu wybierany z listy pracowników ze stanowiskiem
     * "Kierownik Projektu", a nie wpisywany ręcznie. Wcześniejsza kolumna
     * tekstowa nie zdążyła zebrać żadnych danych, więc zamieniamy ją wprost.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('kierownik_projektu');
        });

        Schema::table('organizations', function (Blueprint $table) {
            // contacts.id jest typu int unsigned — klucze muszą się zgadzać.
            $table->unsignedInteger('kierownik_projektu_id')->nullable()->after('zaklad')->index();
            $table->foreign('kierownik_projektu_id')->references('id')->on('contacts');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['kierownik_projektu_id']);
            $table->dropColumn('kierownik_projektu_id');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('kierownik_projektu', 100)->nullable()->after('zaklad');
        });
    }
}
