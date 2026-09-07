<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class KoszDlaDokumentow extends Migration
{
    /**
     * Usunięcie dokumentu było nieodwracalne — wiersz znikał z bazy na dobre.
     * Przy skanach badań i uprawnień jedno kliknięcie za dużo oznaczało
     * konieczność ponownego proszenia pracownika o dokument.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ctn_documents', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('ctn_documents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
