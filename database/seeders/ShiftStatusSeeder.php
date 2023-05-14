<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('shift_status')->insert([
            [
                'title' => 'Urlop wypoczynkowy',
                'code' => 'UW'
            ],
            [
                'title' => 'Urlop okolicznościowy',
                'code' => 'UO'
            ],
            [
                'title' => 'Urlop bezpłatny',
                'code' => 'UB'
            ],
            [
                'title' => 'Odbiór godzin',
                'code' => 'OG'
            ],
            [
                'title' => 'Zwolnienie lekarskie',
                'code' => 'ZL'
            ],
            [
                'title' => 'Praca w biurze',
                'code' => 'B'
            ],
            [
                'title' => 'Badania lekarskie',
                'code' => 'BL'
            ],
            [
                'title' => 'Nieobecnośc pracownika',
                'code' => 'NN'
            ]
        ]);
    }
}
