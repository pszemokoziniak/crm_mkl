<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBazaWiedzyTable extends Migration
{
    /**
     * Baza wiedzy: instrukcje i procedury, które dotąd żyły w mailach
     * i na czacie, przez co przy każdej awarii szukało się ich od nowa.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('baza_wiedzy', function (Blueprint $table) {
            $table->id();
            $table->string('tytul');
            $table->string('kategoria', 80)->nullable()->index();

            // Markdown — w instrukcjach są polecenia i listy kroków, a nie
            // chcemy do tego wciągać edytora WYSIWYG.
            $table->longText('tresc')->nullable();

            // Artykuły techniczne (ścieżki na serwerze, polecenia) nie muszą
            // krążyć po całej firmie.
            $table->boolean('tylko_admin')->default(false)->index();

            $table->integer('kolejnosc')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('baza_wiedzy');
    }
}
