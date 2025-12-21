<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE contact_work_dates MODIFY organization_id INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE contact_work_dates MODIFY contact_id INT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE contact_work_dates MODIFY organization_id INT NOT NULL');
        DB::statement('ALTER TABLE contact_work_dates MODIFY contact_id INT NOT NULL');
    }
};
