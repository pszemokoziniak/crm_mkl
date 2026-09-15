<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nie wszędzie 183 dni liczy się tak samo. Część państw patrzy na rok
 * podatkowy (Austria, Francja, Hiszpania, Luksemburg, Włochy), część na
 * każdy ruchomy okres dwunastu miesięcy (Niemcy, Belgia, Dania, Finlandia,
 * Holandia, Portugalia, Szwecja i inne).
 *
 * Sposób siedzi przy kraju w słowniku, żeby zmiana przepisów albo nowy
 * kontrakt nie wymagały wdrożenia.
 */
return new class extends Migration
{
    private const ROK_KALENDARZOWY = ['Austria', 'Francja', 'Hiszpania', 'Luksemburg', 'Włochy'];

    public function up(): void
    {
        Schema::table('kraj_typs', function (Blueprint $table) {
            $table->string('sposob_183', 20)->default('dwanascie_miesiecy')->after('wymaga_a1');
        });

        DB::table('kraj_typs')
            ->whereIn('name', self::ROK_KALENDARZOWY)
            ->update(['sposob_183' => 'rok_kalendarzowy']);
    }

    public function down(): void
    {
        Schema::table('kraj_typs', function (Blueprint $table) {
            $table->dropColumn('sposob_183');
        });
    }
};
