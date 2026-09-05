<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddKategoriaToNarzedziaTyps extends Migration
{
    /**
     * Kategoria nad typem sprzętu: "Kontener" obejmuje Kontener 3m i 6m,
     * "Manitou" wszystkie modele Manitou. Dzięki temu magazyn pokazuje
     * i ogół, i rozbicie na modele.
     *
     * Zwykły tekst, nie osobny słownik — kategorii jest kilka, a przy typie
     * podpowiadamy już użyte, żeby nie mnożyć zapisów tej samej nazwy.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('narzedzia_typs', function (Blueprint $table) {
            $table->string('kategoria', 100)->nullable()->after('name');
        });

        // Wypełniamy to, co wynika wprost z nazw — reszta zostaje bez kategorii
        // i pokazuje się w magazynie jako osobna pozycja.
        foreach (['Kontener', 'Manitou'] as $kategoria) {
            DB::table('narzedzia_typs')
                ->where('name', 'like', $kategoria.'%')
                ->update(['kategoria' => $kategoria]);
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('narzedzia_typs', function (Blueprint $table) {
            $table->dropColumn('kategoria');
        });
    }
}
