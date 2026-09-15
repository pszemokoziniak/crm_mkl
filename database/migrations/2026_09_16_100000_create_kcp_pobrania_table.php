<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pobranie KCP budowy za miniony miesiąc przez kadry zamyka ten miesiąc:
 * od tej chwili kierownik budowy już go nie poprawia, bo na tych godzinach
 * liczone jest wynagrodzenie.
 *
 * Pobranie w trakcie trwania miesiąca niczego nie zamyka — kierownik ma
 * cały miesiąc na uzupełnianie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kcp_pobrania', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            // Miesiąc, którego dotyczy pobrane KCP, w postaci "2026-09".
            $table->char('okres', 7);
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();

            // Liczy się pierwsze pobranie; kolejne niczego nie zmieniają.
            $table->unique(['organization_id', 'okres']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kcp_pobrania');
    }
};
