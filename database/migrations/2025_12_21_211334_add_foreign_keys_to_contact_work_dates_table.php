<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contact_work_dates', function (Blueprint $table) {
            $table->foreign('organization_id', 'cwd_org_fk')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();

            $table->foreign('contact_id', 'cwd_contact_fk')
                ->references('id')
                ->on('contacts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contact_work_dates', function (Blueprint $table) {
            $table->dropForeign('cwd_org_fk');
            $table->dropForeign('cwd_contact_fk');
        });
    }
};
