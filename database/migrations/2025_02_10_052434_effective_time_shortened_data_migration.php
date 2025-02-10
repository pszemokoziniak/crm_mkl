<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class EffectiveTimeShortenedDataMigration extends Migration
{
    /**
     * DEBUG SQL:
     *
     * SELECT
     * work_from,
     * work_to,
     * effective_work_time,
     * TIMEDIFF(work_to, work_from) AS work_diff,
     * TIME(TIMEDIFF(work_to, work_from)) > TIME(effective_work_time) AS is_overtime
     * FROM building_time_sheets;
     *
     */
    public function up(): void
    {
        DB::table('building_time_sheets')->sql("
            UPDATE building_time_sheets
            SET reduced_working_hours = 1
            WHERE TIME(TIMEDIFF(work_to, work_from)) > TIME(effective_work_time);
        ");
    }

    public function down(): void
    {
        DB::table('building_time_sheets')->sql("
            UPDATE building_time_sheets
            SET reduced_working_hours = 0
        ");
    }
}
