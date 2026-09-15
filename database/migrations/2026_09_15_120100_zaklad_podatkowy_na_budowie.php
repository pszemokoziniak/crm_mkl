<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Budowa uznana za zakład podatkowy: podatek od wynagrodzenia należy się
 * za granicą od pierwszego dnia pracy, więc próg 183 dni nie ma dla niej
 * znaczenia i nie ma o czym ostrzegać.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('zaklad_podatkowy')->default(false)->after('country_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('zaklad_podatkowy');
        });
    }
};
