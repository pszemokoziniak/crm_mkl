<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddShiftStatusToHolidaysTable extends Migration
{
    /**
     * Powód nieobecności — korzystamy z istniejącego słownika shift_status
     * (UW, UB, ZL, ...), tego samego, którym opisane są dni w kartach pracy.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('holidays', function (Blueprint $table) {
            // shift_status.id jest typu int unsigned — klucze muszą się zgadzać.
            $table->unsignedInteger('shift_status_id')->nullable()->after('contact_id')->index();
            $table->foreign('shift_status_id')->references('id')->on('shift_status');
        });

        // Istniejące wpisy powstały w zakładce "Urlopy" — domyślnie urlop wypoczynkowy.
        $urlopWypoczynkowy = DB::table('shift_status')->where('code', 'UW')->value('id');

        if ($urlopWypoczynkowy) {
            DB::table('holidays')->whereNull('shift_status_id')->update([
                'shift_status_id' => $urlopWypoczynkowy,
            ]);
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['shift_status_id']);
            $table->dropColumn('shift_status_id');
        });
    }
}
