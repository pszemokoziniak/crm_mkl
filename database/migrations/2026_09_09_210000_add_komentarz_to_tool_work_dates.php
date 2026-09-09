<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKomentarzToToolWorkDates extends Migration
{
    /**
     * Notatka przy wydaniu sprzętu na budowę — na co go wydano, w jakim jest
     * stanie, komu przekazano. Zgłoszenie Tomasza Budy: pole na cokolwiek,
     * co warto zapisać przy wydaniu.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tool_work_dates', function (Blueprint $table) {
            $table->text('komentarz')->nullable()->after('end');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('tool_work_dates', function (Blueprint $table) {
            $table->dropColumn('komentarz');
        });
    }
}
