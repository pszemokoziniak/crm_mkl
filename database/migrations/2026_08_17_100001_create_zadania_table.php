<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZadaniaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zadania', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('status', 20)->default('do_zrobienia')->index();
            $table->string('priority', 20)->default('normalny');
            // users.id jest w tym projekcie typu int unsigned — klucze muszą się zgadzać.
            $table->unsignedInteger('reporter_id')->index();
            $table->unsignedInteger('assignee_id')->nullable()->index();
            $table->date('deadline')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('reporter_id')->references('id')->on('users');
            $table->foreign('assignee_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zadania');
    }
}
