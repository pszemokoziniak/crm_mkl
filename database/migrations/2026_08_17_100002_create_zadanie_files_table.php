<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZadanieFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zadanie_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zadanie_id')->index();
            // Załącznik dodany w komentarzu — pokazujemy go pod tym komentarzem.
            $table->unsignedBigInteger('note_id')->nullable()->index();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('zadanie_id')->references('id')->on('zadania')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zadanie_files');
    }
}
