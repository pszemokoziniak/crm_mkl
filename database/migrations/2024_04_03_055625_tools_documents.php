<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ToolsDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tools_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyText('name');
            $table->string('typ');
            $table->tinyText('filename');
            $table->tinyText('path');
            $table->unsignedInteger('tool_id')->nullable();
            $table->foreign('tool_id')->references('id')->on('narzedzias');
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
        Schema::dropIfExists('tools_documents');
    }
}
