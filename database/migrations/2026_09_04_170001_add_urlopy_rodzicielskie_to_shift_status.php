<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AddUrlopyRodzicielskieToShiftStatus extends Migration
{
    /**
     * Nowe powody nieobecności. Słownik jest edytowalny z Ustawień, więc
     * dopisujemy tylko brakujące pozycje — istniejących nie ruszamy.
     */
    private const POZYCJE = [
        ['code' => 'UOJ', 'title' => 'Urlop ojcowski'],
        ['code' => 'UM', 'title' => 'Urlop macierzyński'],
    ];

    /**
     * @return void
     */
    public function up()
    {
        $teraz = Carbon::now();

        foreach (self::POZYCJE as $pozycja) {
            $istnieje = DB::table('shift_status')
                ->where('code', $pozycja['code'])
                ->orWhere('title', $pozycja['title'])
                ->exists();

            if ($istnieje) {
                continue;
            }

            DB::table('shift_status')->insert($pozycja + [
                'created_at' => $teraz,
                'updated_at' => $teraz,
            ]);
        }
    }

    /**
     * Usuwamy tylko wtedy, gdy nikt jeszcze na te powody nie odnotował
     * nieobecności — cudzych danych migracja nie kasuje.
     *
     * @return void
     */
    public function down()
    {
        foreach (self::POZYCJE as $pozycja) {
            $status = DB::table('shift_status')->where('code', $pozycja['code'])->first();

            if (! $status) {
                continue;
            }

            $uzywany = DB::table('holidays')->where('shift_status_id', $status->id)->exists();

            if (! $uzywany) {
                DB::table('shift_status')->where('id', $status->id)->delete();
            }
        }
    }
}
