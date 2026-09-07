<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class KoszDlaA1 extends Migration
{
    /**
     * Tabele bhps i uprawnienias miały deleted_at od dawna, tylko modele
     * nie używały SoftDeletes — kolumna leżała bezużytecznie, a kasowanie
     * i tak było nieodwracalne. a1_s nie ma jej wcale, więc dokładamy.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('a1_s', 'deleted_at')) {
            Schema::table('a1_s', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('a1_s', 'deleted_at')) {
            Schema::table('a1_s', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}
