<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPowiadomieniaMailoweKadr extends Migration
{
    /**
     * E-mail do kadr o zmianach kadrowych czekających w zakładce.
     * Odbiorców wskazuje się znacznikiem przy koncie użytkownika, żeby
     * dało się kogoś dopisać albo wypisać bez zmiany w kodzie.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('powiadomienia_kadrowe')->default(false)->after('active');
        });

        Schema::table('zmiany_kadrowe', function (Blueprint $table) {
            // Znacznik na paczce — żeby ta sama seria zmian nie poszła dwa razy.
            $table->timestamp('mail_wyslany_at')->nullable()->after('handled_at');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('powiadomienia_kadrowe');
        });

        Schema::table('zmiany_kadrowe', function (Blueprint $table) {
            $table->dropColumn('mail_wyslany_at');
        });
    }
}
