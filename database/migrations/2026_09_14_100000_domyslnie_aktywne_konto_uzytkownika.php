<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kolumna `active` powstała bez wartości domyślnej, a jest NOT NULL, więc
 * zapis pomijający ją wstawiał zero — czyli konto zablokowane. Tak powstawał
 * każdy nowy użytkownik: dostawał hasło mailem i po jego wpisaniu widział
 * "Konto zablokowane".
 *
 * Kod zakładający konto ustawia teraz `active` wprost; wartość domyślna jest
 * drugim zabezpieczeniem, żeby żadna inna droga zapisu nie stworzyła po cichu
 * konta, którym nie da się zalogować.
 *
 * Kont już istniejących nie ruszamy — część z nich jest zablokowana celowo.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY active INT NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY active INT NOT NULL');
    }
};
