<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        // if account does not exist - create
        User::factory()->create([
            'account_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@mkl.com',
            'password' => 'secret',
            'owner' => true,
        ]);
    }
}
