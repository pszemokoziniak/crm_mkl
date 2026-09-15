<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dodałem kolumnę `zaklad_podatkowy`, nie zauważywszy, że formularz budowy
 * od dawna ma pole "Zakład podatkowy" (kolumna `zaklad`, wartości tak/nie).
 * Siedem budów jest tam już oznaczonych. Zostaje jedno pole, to starsze
 * i wypełnione; nowe znika, zanim ktokolwiek zdąży go użyć.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('zaklad_podatkowy');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('zaklad_podatkowy')->default(false)->after('country_id');
        });
    }
};
