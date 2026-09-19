<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Koszty podróży i kwater: wpis kosztu przypięty do pracownika, do budowy
 * albo do obu; kwota w walucie i po kursie NBP w PLN; pokój (typ "nocleg")
 * z liczbą miejsc i zakwaterowanymi. Wynagrodzeń i premii tu nie ma —
 * HRM odpowiada tylko na pytanie, ile kosztowały przejazdy i noclegi.
 */
class CreateKosztyTables extends Migration
{
    public function up(): void
    {
        Schema::create('typy_kosztow', function (Blueprint $table) {
            $table->id();
            $table->string('nazwa', 100)->unique();
            // Koszt budowy tego typu domyślnie dzieli się na pracowników.
            $table->boolean('dzielony')->default(false);
            // Typ "nocleg" włącza liczbę miejsc i przypisywanie ludzi do pokoju.
            $table->boolean('nocleg')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kursy_walut', function (Blueprint $table) {
            $table->id();
            $table->char('waluta', 3);
            $table->date('data');
            $table->decimal('kurs', 12, 6);
            $table->string('zrodlo', 10)->default('nbp');
            $table->timestamps();
            $table->unique(['waluta', 'data']);
        });

        Schema::create('koszty', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('typ_kosztu_id')->index();
            // contacts.id i organizations.id to int unsigned (stary schemat).
            $table->unsignedInteger('organization_id')->nullable()->index();
            $table->unsignedInteger('contact_id')->nullable()->index();
            $table->date('data')->index();
            $table->decimal('kwota', 12, 2);
            $table->char('waluta', 3)->default('PLN');
            $table->decimal('kurs', 12, 6)->nullable();
            $table->boolean('kurs_reczny')->default(false);
            $table->decimal('kwota_pln', 12, 2);
            $table->text('opis')->nullable();
            // Pokój: za jaki okres i ile miejsc.
            $table->date('od')->nullable();
            $table->date('do')->nullable();
            $table->unsignedSmallInteger('miejsc')->nullable();
            $table->boolean('dzielony')->default(false);
            $table->string('plik_sciezka')->nullable();
            $table->string('plik_nazwa')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Osoby na koszcie: zakwaterowani w pokoju (z datami) albo ręcznie
        // wskazani uczestnicy dzielonego kosztu (bez dat).
        Schema::create('koszty_osoby', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('koszt_id')->index();
            $table->unsignedInteger('contact_id')->index();
            $table->date('od')->nullable();
            $table->date('do')->nullable();
            $table->timestamps();
        });

        // Pięć typów na start; biuro dopisuje kolejne w Ustawieniach.
        $teraz = now();
        DB::table('typy_kosztow')->insert([
            ['nazwa' => 'Bilet lotniczy', 'dzielony' => false, 'nocleg' => false, 'created_at' => $teraz, 'updated_at' => $teraz],
            ['nazwa' => 'Bilet kolejowy / autobusowy', 'dzielony' => false, 'nocleg' => false, 'created_at' => $teraz, 'updated_at' => $teraz],
            ['nazwa' => 'Paliwo i opłaty drogowe', 'dzielony' => true, 'nocleg' => false, 'created_at' => $teraz, 'updated_at' => $teraz],
            ['nazwa' => 'Wynajem samochodu', 'dzielony' => true, 'nocleg' => false, 'created_at' => $teraz, 'updated_at' => $teraz],
            ['nazwa' => 'Kwatera / pokój', 'dzielony' => true, 'nocleg' => true, 'created_at' => $teraz, 'updated_at' => $teraz],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('koszty_osoby');
        Schema::dropIfExists('koszty');
        Schema::dropIfExists('kursy_walut');
        Schema::dropIfExists('typy_kosztow');
    }
}
