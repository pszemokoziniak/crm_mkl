<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id')->index();
            $table->string('name', 500);
            $table->string('nazwaBud', 100)->nullable();
            $table->string('numerBud', 50)->nullable();
            $table->string('city', 450)->nullable();
            $table->integer('kierownikBud_id')->index();
            $table->string('zaklad', 350)->nullable();
            $table->integer('country_id')->index();
            $table->string('addressBud', 1500)->nullable();
            $table->string('addressKwat', 1500)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organizations');
    }
}
