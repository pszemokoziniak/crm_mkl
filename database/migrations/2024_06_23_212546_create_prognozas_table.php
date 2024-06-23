<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrognozasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prognozas', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_id')->unsigned()->index()->nullable(false);
            $table->unsignedBigInteger('prognoza_dates_id')->index();
            $table->integer('workers_count');
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->foreign('prognoza_dates_id')->references('id')->on('prognoza_dates');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prognozas');
    }
}
