<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the initial admin account with requested secure credentials.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'napoli9087italy@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('issa@napoli10#7'),
            ]
        );
    }
}
