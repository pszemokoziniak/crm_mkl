<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('narzedzias', 'narzedzia_typ_id')) {
            Schema::table('narzedzias', function (Blueprint $table) {
                $table->unsignedBigInteger('narzedzia_typ_id')->nullable()->index()->after('name');
            });
        }

        $now = now();

        // Zasiej katalog typów z istniejących nazw i podłącz sztuki.
        // Nazwy typu "Kontener 6m (serial no. XXX)" rozbijamy na typ "Kontener 6m"
        // + numer seryjny XXX (jeśli pole numeru było puste).
        DB::table('narzedzias')->orderBy('id')->get()->each(function ($tool) use ($now) {
            $raw = trim((string) ($tool->name ?? ''));
            $serial = null;

            if (preg_match('/^(.*?)\s*\(serial no\.\s*(.+?)\)\s*$/u', $raw, $m)) {
                $typeName = trim($m[1]);
                $serial = trim($m[2]);
            } else {
                $typeName = $raw;
            }

            if ($typeName === '') {
                $typeName = 'Nieznany';
            }

            $typId = DB::table('narzedzia_typs')->where('name', $typeName)->value('id');
            if (! $typId) {
                $typId = DB::table('narzedzia_typs')->insertGetId([
                    'name' => $typeName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $update = [
                'narzedzia_typ_id' => $typId,
                'name' => $typeName, // migawka = czysta nazwa typu
            ];
            if ($serial && empty($tool->numer_seryjny)) {
                $update['numer_seryjny'] = $serial;
            }

            DB::table('narzedzias')->where('id', $tool->id)->update($update);
        });
    }

    public function down(): void
    {
        Schema::table('narzedzias', function (Blueprint $table) {
            $table->dropColumn('narzedzia_typ_id');
        });
    }
};
