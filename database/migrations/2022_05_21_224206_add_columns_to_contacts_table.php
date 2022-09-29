<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->date('birth_date')->nullable();
            $table->string('pesel', 50)->nullable();
            $table->string('idCard_number', 50)->nullable();
            $table->date('idCard_date')->nullable();
            $table->string('position', 50)->nullable();
            $table->date('work_start')->nullable();
            $table->date('work_end')->nullable();
            $table->date('ekuz')->nullable();
            $table->unsignedBigInteger('funkcja_id')->nullable();
            $table->foreign('funkcja_id')->references('id')->on('funkcjas');
            $table->string('miejsce_urodzenia')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->date('birth_date');
            $table->string('pesel', 50);
            $table->string('idCard_number', 50);
            $table->date('idCard_date');
            $table->string('position', 50);
            $table->string('funkcja', 50);
            $table->date('work_start');
            $table->date('work_end');
            $table->date('ekuz');
        });
    }
}
