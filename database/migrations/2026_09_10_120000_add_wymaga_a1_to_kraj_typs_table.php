<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A1 potwierdza, gdzie pracownik podlega ubezpieczeniu, kiedy firma wysyła go
 * do innego państwa. Na kontrakcie w Polsce nie jest do niczego potrzebne, a
 * system dopominał się o nie przy każdym pracowniku w kraju.
 *
 * O tym, czy kraj wymaga A1, decyduje słownik, nie kod: gdyby doszedł kolejny
 * kraj albo zmieniły się przepisy, biuro odznacza pole samo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kraj_typs', function (Blueprint $table) {
            $table->boolean('wymaga_a1')->default(true)->after('name');
        });

        // Wyjątkiem jest kraj macierzysty — wpisany w słowniku jako "Polska".
        DB::table('kraj_typs')
            ->whereRaw("LOWER(TRIM(name)) = 'polska'")
            ->update(['wymaga_a1' => false]);
    }

    public function down(): void
    {
        Schema::table('kraj_typs', function (Blueprint $table) {
            $table->dropColumn('wymaga_a1');
        });
    }
};
