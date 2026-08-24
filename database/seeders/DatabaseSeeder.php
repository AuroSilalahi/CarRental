<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\CarType;
use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Admin Account
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

        // 2. Create Sample Customer Account
        User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'phone' => '081298765432',
                'address' => 'Jl. Gatot Subroto No. 88',
                'city' => 'Medan',
                'province' => 'Sumatera Utara',
                'account_status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        // 3. Create Sample Cars Fleet
        Car::firstOrCreate(
            ['license_plate' => 'BK 1234 ABC'],
            [
                'brand' => 'Toyota',
                'model' => 'Innova Zenix',
                'type' => 'MPV',
                'passenger_capacity' => 7,
                'colour' => 'Hitam Metalik',
                'year' => 2024,
                'daily_rate_idr' => 750000,
                'is_available' => true,
                'is_luxury_brand' => false,
                'luxury_multiplier' => 1.0,
            ]
        );

        Car::firstOrCreate(
            ['license_plate' => 'BK 5678 XYZ'],
            [
                'brand' => 'Honda',
                'model' => 'HR-V SE',
                'type' => 'SUV',
                'passenger_capacity' => 5,
                'colour' => 'Putih Mutiara',
                'year' => 2023,
                'daily_rate_idr' => 600000,
                'is_available' => true,
                'is_luxury_brand' => false,
                'luxury_multiplier' => 1.0,
            ]
        );

        Car::firstOrCreate(
            ['license_plate' => 'BK 9999 VIP'],
            [
                'brand' => 'Mercedes-Benz',
                'model' => 'E-Class E 300',
                'type' => 'Sedan',
                'passenger_capacity' => 5,
                'colour' => 'Hitam Elegansi',
                'year' => 2024,
                'daily_rate_idr' => 2500000,
                'is_available' => true,
                'is_luxury_brand' => true,
                'luxury_multiplier' => 1.5,
            ]
        );

        Car::firstOrCreate(
            ['license_plate' => 'BK 8888 LUX'],
            [
                'brand' => 'Toyota',
                'model' => 'Alphard HEV',
                'type' => 'MPV',
                'passenger_capacity' => 7,
                'colour' => 'Putih',
                'year' => 2024,
                'daily_rate_idr' => 3000000,
                'is_available' => true,
                'is_luxury_brand' => true,
                'luxury_multiplier' => 1.8,
            ]
        );
    }
}
