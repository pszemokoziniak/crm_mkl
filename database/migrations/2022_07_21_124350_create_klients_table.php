<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKlientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('klients', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_id')->index();
            $table->string('nameFirma', 500);
            $table->string('adres', 1000)->nullable();
            $table->string('city', 1000)->nullable();
            $table->integer('country_id')->index();
            $table->string('nameKontakt', 500)->nullable();
            $table->string('phone', 4500)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('klients');
    }
}
