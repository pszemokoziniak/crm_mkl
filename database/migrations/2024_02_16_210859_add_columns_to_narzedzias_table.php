<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToNarzedziasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('narzedzias', function (Blueprint $table) {
            $table->string('numer_seryjny');
            $table->date('waznosc_badan');
            $table->string('photo_path', 100)->nullable();
            $table->tinyText('filename')->nullable();
            $table->tinyText('path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('narzedzias', function (Blueprint $table) {
            $table->dropColumn('numer_seryjny');
            $table->dropColumn('waznosc_badan');
            $table->dropColumn('photo_path');
            $table->dropColumn('filename');
            $table->dropColumn('path');
        });
    }
}
