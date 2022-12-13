<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameCtnDocumentsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ctn_documents', function(Blueprint $table) {
            //$table->renameColumn('typ', 'dokumentytyp_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ctn_documents', function(Blueprint $table) {
            $table->renameColumn('dokumentytyp_id', 'typ');
        });
    }
}
