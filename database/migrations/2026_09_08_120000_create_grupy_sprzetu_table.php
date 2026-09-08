<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grupa sprzętu przestaje być tekstem przy modelu, a staje się rekordem.
 *
 * Dopóki grupą była kolumna `kategoria`, grupa istniała wyłącznie tak długo,
 * jak jakiś model nosił jej nazwę. Nie dało się więc założyć grupy i dopiero
 * potem wrzucać do niej sprzęt — a o to właśnie prosił magazyn. Do tego
 * literówka w nazwie cicho tworzyła drugą grupę obok istniejącej.
 */
class CreateGrupySprzetuTable extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        Schema::create('grupy_sprzetu', function (Blueprint $table) {
            $table->id();
            $table->string('nazwa', 100)->unique();
            $table->timestamps();
        });

        Schema::table('narzedzia_typs', function (Blueprint $table) {
            $table->foreignId('grupa_id')->nullable()->after('name')
                ->constrained('grupy_sprzetu')->nullOnDelete();
        });

        // Przenosimy to, co już jest. Nazwy przycinamy, bo spacja na końcu
        // dawała dotąd osobną "grupę" nie do odróżnienia na ekranie.
        $nazwy = DB::table('narzedzia_typs')
            ->whereNotNull('kategoria')
            ->pluck('kategoria')
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique()
            ->values();

        foreach ($nazwy as $nazwa) {
            $id = DB::table('grupy_sprzetu')->insertGetId([
                'nazwa' => $nazwa,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('narzedzia_typs')
                ->whereRaw('TRIM(kategoria) = ?', [$nazwa])
                ->update(['grupa_id' => $id]);
        }

        Schema::table('narzedzia_typs', function (Blueprint $table) {
            $table->dropColumn('kategoria');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('narzedzia_typs', function (Blueprint $table) {
            $table->string('kategoria', 100)->nullable()->after('name');
        });

        DB::table('narzedzia_typs as t')
            ->join('grupy_sprzetu as g', 'g.id', '=', 't.grupa_id')
            ->update(['t.kategoria' => DB::raw('g.nazwa')]);

        Schema::table('narzedzia_typs', function (Blueprint $table) {
            $table->dropForeign(['grupa_id']);
            $table->dropColumn('grupa_id');
        });

        Schema::dropIfExists('grupy_sprzetu');
    }
}
