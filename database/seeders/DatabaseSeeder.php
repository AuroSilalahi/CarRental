<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Only 1 Default Admin Account
        User::firstOrCreate(
            ['email' => 'admin@carrental.com'],
            [
                'name' => 'Administrator CarRental',
                'password' => Hash::make('password'),
                'phone' => '081234567890',
                'address' => 'Jl. Pemuda No. 1',
                'city' => 'Medan',
                'province' => 'Sumatera Utara',
                'account_status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ]
        );
    }
}
