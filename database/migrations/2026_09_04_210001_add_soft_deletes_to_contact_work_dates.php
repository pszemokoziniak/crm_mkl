<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToContactWorkDates extends Migration
{
    /**
     * Pobyty na budowach kasowaliśmy na twardo, więc przywrócenie pracownika
     * z archiwum nie miało czego odtworzyć. Od teraz znikają tak jak sam
     * pracownik — zostają w bazie i wracają razem z nim.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contact_work_dates', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('contact_work_dates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
