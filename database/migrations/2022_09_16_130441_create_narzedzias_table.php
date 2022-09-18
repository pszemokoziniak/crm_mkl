<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNarzedziasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('narzedzias', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->integer('ilosc');
            $table->integer('iloscBudowa')->nullable();
            $table->integer('iloscMagazyn')->nullable();
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
        Schema::dropIfExists('narzedzias');
    }
}
