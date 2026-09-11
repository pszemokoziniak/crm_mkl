<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pliki sprzętu pokazywały się pod nazwą, z jaką przyszły z aparatu albo
 * skanera ("IMG_20240513_113245.jpg"), i nie dało się jej zmienić. Nazwa
 * widoczna jest teraz osobno od nazwy pliku na dysku — zmiana podpisu nie
 * rusza samego pliku ani odnośników do niego.
 *
 * `glowne` wskazuje zdjęcie na kartę sprzętu i miniaturkę na liście. Bez
 * tego brało się pierwsze wgrane, więc żeby zmienić miniaturkę, trzeba było
 * kasować zdjęcia i wgrywać je na nowo w odpowiedniej kolejności.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_files', function (Blueprint $table) {
            $table->string('nazwa')->nullable()->after('filename');
            $table->boolean('glowne')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('tool_files', function (Blueprint $table) {
            $table->dropColumn(['nazwa', 'glowne']);
        });
    }
};
