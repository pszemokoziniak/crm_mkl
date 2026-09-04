<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarsztatToOrganizations extends Migration
{
    /**
     * Warsztaty (Łuków, Siedlce) prowadzimy jak budowy, bo tylko tak da się
     * przypisać pracownika i rozliczyć mu godziny. Znacznik pozwala je odróżnić
     * tam, gdzie liczymy "ile mamy budów" albo planujemy obsadę.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('warsztat')->default(false)->after('zaklad');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('warsztat');
        });
    }
}
