<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKrajTypsIdToA1STable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('a1_s', function (Blueprint $table) {
            $table->unsignedBigInteger('kraj_typs_id')->index()->nullable();
            $table->foreign('kraj_typs_id')->references('id')->on('kraj_typs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('a1_s', function (Blueprint $table) {
            $table->dropColumn('kraj_typs_id');
        });
    }
}
