<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameNarzedziasColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('narzedzias', function(Blueprint $table) {
            $table->renameColumn('ilosc', 'ilosc_all');
            $table->renameColumn('iloscBudowa', 'ilosc_budowa');
            $table->renameColumn('iloscMagazyn', 'ilosc_magazyn');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('narzedzias', function(Blueprint $table) {
            $table->renameColumn('ilosc_all', 'ilosc');
            $table->renameColumn('ilosc_budowa', 'iloscBudowa');
            $table->renameColumn('ilosc_magazyn', 'iloscMagazyn');
        });
    }
}
