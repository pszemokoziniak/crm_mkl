<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Zgłoszenia idą też mailem do tych, którzy dostają e-mail o zmianach kadrowych. */
class AddMailWyslanyAtToZgloszeniaKierownikow extends Migration
{
    public function up(): void
    {
        Schema::table('zgloszenia_kierownikow', function (Blueprint $table) {
            $table->timestamp('mail_wyslany_at')->nullable()->after('odpowiedz');
        });
    }

    public function down(): void
    {
        Schema::table('zgloszenia_kierownikow', function (Blueprint $table) {
            $table->dropColumn('mail_wyslany_at');
        });
    }
}
