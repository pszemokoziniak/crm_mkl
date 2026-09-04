<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AddSwiadczenieRehabilitacyjneToShiftStatus extends Migration
{
    /**
     * Kolejny powód nieobecności. Jak przy urlopach rodzicielskich —
     * dopisujemy tylko brakujące, istniejących nie ruszamy.
     */
    private const POZYCJE = [
        ['code' => 'ŚR', 'title' => 'Świadczenie rehabilitacyjne'],
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
     * Usuwamy tylko wtedy, gdy nikt jeszcze na ten powód nie odnotował
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
