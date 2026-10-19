<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD', 'password123');

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator',
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'cashier@example.com'],
            [
                'name' => 'Kasir Demo',
                'password' => Hash::make('password123'),
                'role' => 'cashier',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'mechanic@example.com'],
            [
                'name' => 'Mekanik Demo',
                'password' => Hash::make('password123'),
                'role' => 'mechanic',
                'is_active' => true,
            ]
        );

        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'shop_name'],
                ['value' => 'Bengkel Alat Berat & Sparepart Demo', 'created_at' => now(), 'updated_at' => now()]
            );
            DB::table('settings')->updateOrInsert(
                ['key' => 'tax_rate_percent'],
                ['value' => '0', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('customers')) {
            DB::table('customers')->updateOrInsert(
                ['code' => 'CUST-UMUM'],
                [
                    'name' => 'Pelanggan Umum',
                    'type' => 'individual',
                    'phone' => '-',
                    'address' => '-',
                    'payment_term_days' => 0,
                    'notes' => 'Customer default untuk penjualan langsung (counter sale)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
