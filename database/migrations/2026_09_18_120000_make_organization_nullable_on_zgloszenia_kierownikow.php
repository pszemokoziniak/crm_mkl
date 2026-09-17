<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wniosek urlopowy z telefonu od kogoś między budowami zatwierdzają kadry;
 * powstałe z niego zgłoszenie nie ma budowy.
 */
class MakeOrganizationNullableOnZgloszeniaKierownikow extends Migration
{
    public function up(): void
    {
        Schema::table('zgloszenia_kierownikow', function (Blueprint $table) {
            $table->unsignedInteger('organization_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('zgloszenia_kierownikow', function (Blueprint $table) {
            $table->unsignedInteger('organization_id')->nullable(false)->change();
        });
    }
}
