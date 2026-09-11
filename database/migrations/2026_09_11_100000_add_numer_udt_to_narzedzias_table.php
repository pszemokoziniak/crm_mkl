<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numer ewidencyjny nadany przez Urząd Dozoru Technicznego. Dotyczy sprzętu
 * pod dozorem (podesty, żurawie, wózki) i to o niego pyta inspektor przy
 * kontroli — dotąd trzymany był poza systemem, w papierach.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('narzedzias', function (Blueprint $table) {
            $table->string('numer_udt', 100)->nullable()->after('numer_seryjny');
        });
    }

    public function down(): void
    {
        Schema::table('narzedzias', function (Blueprint $table) {
            $table->dropColumn('numer_udt');
        });
    }
};
