<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PowiazanieDokumentuZWpisem extends Migration
{
    /**
     * Dokument wiązał się dotąd tylko z pracownikiem i typem, więc skan
     * badania lądował w worku "wszystkie badania tego człowieka" i nie było
     * wiadomo, którego wpisu dotyczy. Teraz można go przypiąć do konkretnego
     * badania, szkolenia, A1 czy PBIOZ — luźne dokumenty nadal działają,
     * dlatego powiązanie jest opcjonalne.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ctn_documents', function (Blueprint $table) {
            $table->nullableMorphs('zrodlo');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('ctn_documents', function (Blueprint $table) {
            $table->dropMorphs('zrodlo');
        });
    }
}
