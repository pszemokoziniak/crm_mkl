<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Referencja do klienta z bazy CRM (crm_mklv2). Nazwa klienta zostaje
            // w kolumnie `name` jako migawka — budowa działa nawet gdy CRM jest
            // niedostępny albo klient zostanie tam zmieniony/usunięty.
            $table->unsignedBigInteger('crm_client_id')->nullable()->index()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('crm_client_id');
        });
    }
};
