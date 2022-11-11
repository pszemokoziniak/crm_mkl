<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FunkcjaSeeder extends Seeder
{
    public function run()
    {
        DB::table('funkcjas')->insert([
            [
                'name' => 'Murarz'
            ]
        ]);
    }
}
