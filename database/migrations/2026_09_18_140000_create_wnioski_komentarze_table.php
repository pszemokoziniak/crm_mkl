<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Rozmowa przypięta do wniosku urlopowego: pracownik z telefonu, kierownik/kadry z HRM. */
class CreateWnioskiKomentarzeTable extends Migration
{
    public function up(): void
    {
        Schema::create('wnioski_komentarze', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wniosek_id')->index();
            // NULL = pisze pracownik ze swojej strony (nie ma konta w HRM).
            $table->unsignedInteger('user_id')->nullable();
            $table->text('tresc');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wnioski_komentarze');
    }
}
