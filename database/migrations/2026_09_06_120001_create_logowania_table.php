<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogowaniaTable extends Migration
{
    /**
     * Rejestr logowań: kto, kiedy, skąd i czy się udało.
     * Dotąd zostawała tylko data ostatniego wejścia, nadpisywana przy każdym
     * kolejnym — nie dało się sprawdzić, czy ktoś próbował się dostać na cudze
     * konto ani jak często ktoś z systemu korzysta.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logowania', function (Blueprint $table) {
            $table->id();

            // Bez klucza obcego: nieudana próba może dotyczyć konta, którego
            // nie ma, a usunięcie użytkownika nie powinno kasować historii.
            $table->unsignedInteger('user_id')->nullable()->index();

            // Wpisany adres — przy nieudanej próbie to jedyny ślad, kogo dotyczyła.
            $table->string('email', 150)->nullable();
            $table->boolean('udane')->default(false)->index();
            $table->string('powod', 60)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('przegladarka', 255)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logowania');
    }
}
