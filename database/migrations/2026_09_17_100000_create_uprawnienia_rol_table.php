<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nadpisania macierzy uprawnień z ekranu Ustawienia → Uprawnienia ról.
 * Wiersz dla roli = jej pełna lista uprawnień; brak wiersza = domyślne
 * z App\Uprawnienia\Macierz. Osobno dziennik zmian: kto, kiedy, co dodał
 * i co odebrał.
 */
class CreateUprawnieniaRolTable extends Migration
{
    public function up(): void
    {
        Schema::create('uprawnienia_rol', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('rola')->unique();
            $table->json('uprawnienia');
            // users.id to int unsigned (stary schemat), nie bigint.
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('uprawnienia_zmiany', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('rola')->index();
            $table->unsignedInteger('user_id')->nullable();
            $table->json('dodane');
            $table->json('odebrane');
            $table->boolean('przywrocenie')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uprawnienia_zmiany');
        Schema::dropIfExists('uprawnienia_rol');
    }
}
