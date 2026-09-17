<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Zgłoszenie "brak dokumentu pracownika": którego dokumentu brakuje. */
class AddDokumentToZgloszeniaKierownikow extends Migration
{
    public function up(): void
    {
        Schema::table('zgloszenia_kierownikow', function (Blueprint $table) {
            $table->string('dokument', 30)->nullable()->after('rodzaj');
        });
    }

    public function down(): void
    {
        Schema::table('zgloszenia_kierownikow', function (Blueprint $table) {
            $table->dropColumn('dokument');
        });
    }
}
