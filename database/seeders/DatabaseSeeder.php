<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BookingLandingPageSeeder::class);

        User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
