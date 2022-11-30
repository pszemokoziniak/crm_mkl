<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FeastDays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('feast_days', function (Blueprint $table) {
            $table->id();
            $table->integer('country_id');
            $table->char('name');
            $table->dateTimeTz('fest_date');

            $table->foreign('country_id')->references('id')->on('kraj_typs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('feast_days');
    }
}
