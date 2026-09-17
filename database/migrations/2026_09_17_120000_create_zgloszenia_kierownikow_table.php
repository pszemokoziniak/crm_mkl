<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zgłoszenie od kierownika budowy: "ten pracownik zjeżdża / idzie na urlop /
 * ma być przeniesiony" z datami i ewentualnym skanem. Kierownik niczego
 * nie zmienia — zmianę pobytu albo nieobecność wstawiają kadry i zamykają
 * zgłoszenie.
 */
class CreateZgloszeniaKierownikowTable extends Migration
{
    public function up(): void
    {
        Schema::create('zgloszenia_kierownikow', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('contact_id')->index();
            $table->unsignedInteger('organization_id')->index();
            // users.id to int unsigned (stary schemat), nie bigint.
            $table->unsignedInteger('user_id')->nullable();
            $table->string('rodzaj', 20);
            $table->date('od')->nullable();
            $table->date('do')->nullable();
            $table->text('uwaga')->nullable();
            $table->string('plik_sciezka')->nullable();
            $table->string('plik_nazwa')->nullable();
            $table->string('status', 20)->default('nowe')->index();
            $table->unsignedInteger('obsluzyl_id')->nullable();
            $table->timestamp('obsluzone_at')->nullable();
            $table->text('odpowiedz')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zgloszenia_kierownikow');
    }
}
