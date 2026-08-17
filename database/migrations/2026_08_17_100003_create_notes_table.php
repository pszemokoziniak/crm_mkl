<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id'); // autor komentarza (users.id = int unsigned)
            $table->string('notable_type'); // np. App\Models\Zadanie
            $table->unsignedBigInteger('notable_id');
            $table->text('body');
            // Wpis wygenerowany przez system (np. zmiana statusu) — nieedytowalny.
            $table->boolean('system')->default(false);
            $table->timestamps();

            $table->index(['notable_type', 'notable_id']);
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notes');
    }
}
