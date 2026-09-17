<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Strona pracownika na telefon: wejście osobistym linkiem + PIN, bez konta
 * w HRM. Pracownik składa wniosek urlopowy, zatwierdza kierownik, kadry
 * dostają zgłoszenie jak dotąd.
 */
class CreateDostepPracownikaAndWnioskiUrlopowe extends Migration
{
    public function up(): void
    {
        Schema::create('dostep_pracownika', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('contact_id')->unique();
            // W bazie tylko skrót linku — jak hasło. Sam link widzi wydający raz.
            $table->string('token_hash', 64)->unique();
            $table->string('pin_hash')->nullable();
            $table->unsignedTinyInteger('proby_pin')->default(0);
            $table->timestamp('zablokowany_do')->nullable();
            $table->unsignedInteger('wydal_id')->nullable();
            $table->timestamp('wydany_at')->nullable();
            $table->timestamp('wyslany_mail_at')->nullable();
            $table->timestamp('wyslany_sms_at')->nullable();
            $table->timestamp('ostatnie_wejscie_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wnioski_urlopowe', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('contact_id')->index();
            $table->string('rodzaj', 10);
            $table->date('od');
            $table->date('do');
            $table->text('uwaga')->nullable();
            $table->string('status', 20)->default('zlozony')->index();
            $table->unsignedInteger('rozpatrzyl_id')->nullable();
            $table->timestamp('rozpatrzony_at')->nullable();
            $table->text('odpowiedz')->nullable();
            $table->unsignedBigInteger('zgloszenie_id')->nullable();
            $table->timestamps();
        });

        Schema::table('zgloszenia_kierownikow', function (Blueprint $table) {
            $table->unsignedBigInteger('wniosek_id')->nullable()->after('plik_nazwa');
        });
    }

    public function down(): void
    {
        Schema::table('zgloszenia_kierownikow', function (Blueprint $table) {
            $table->dropColumn('wniosek_id');
        });
        Schema::dropIfExists('wnioski_urlopowe');
        Schema::dropIfExists('dostep_pracownika');
    }
}
