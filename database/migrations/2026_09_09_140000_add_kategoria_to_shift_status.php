<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddKategoriaToShiftStatus extends Migration
{
    /**
     * Do czego zaliczyć godziny z danym statusem w statystykach budowy.
     *
     * Słownik statusów prowadzi biuro i bywa w nim wszystko (jest tam pozycja
     * "Sraczka"), więc rozpoznawanie urlopu czy zwolnienia po nazwie albo po
     * identyfikatorze rozjechałoby się przy pierwszej zmianie. Kategoria to
     * zwykłe pole przy statusie — kolejny status biuro przypisze samo.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_status', function (Blueprint $table) {
            $table->string('kategoria', 20)->nullable()->after('title')->index();
        });

        // Wypełniamy tylko jednoznaczne. Reszta czeka na decyzję biura
        // i do czasu przypisania liczy się w statystykach jako "inne".
        $przypisania = [
            'praca' => ['Praca w biurze'],
            'urlop' => [
                'Urlop wypoczynkowy', 'Urlop okolicznościowy', 'Urlop bezpłatny',
                'Urlop na żądanie', 'Urlop ojcowski', 'Urlop macierzyński',
            ],
            'zwolnienie' => ['Zwolnienie lekarskie', 'Świadczenie rehabilitacyjne'],
            'nieobecnosc' => ['Nieobecność nieusprawiedliwiona'],
            'swieto' => ['Święto'],
        ];

        foreach ($przypisania as $kategoria => $nazwy) {
            DB::table('shift_status')->whereIn('title', $nazwy)->update(['kategoria' => $kategoria]);
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('shift_status', function (Blueprint $table) {
            $table->dropColumn('kategoria');
        });
    }
}
