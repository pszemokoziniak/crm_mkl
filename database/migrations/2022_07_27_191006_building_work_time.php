<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BuildingWorkTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shiftStatusSeeder', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->char('code', 10);
        });

        // @TODO consider using proper names for domain
        Schema::create('building_time_sheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id')->index();
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('shift_status_id')->nullable();
            $table->dateTimeTz('work_day'); // @TODO only date
            $table->dateTimeTz('work_from');
            $table->dateTimeTz('work_to');
            $table->char('effective_work_time'); // consider is it a good name ?

            // constraints
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->foreign('shift_status_id')->references('id')->on('shiftStatusSeeder');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('shiftStatusSeeder');
        Schema::dropIfExists('building_work_time');
    }
}
