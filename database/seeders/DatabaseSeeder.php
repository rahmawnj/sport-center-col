<?php

namespace Database\Seeders;

use App\Models\Role;
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
        $superadminRole = Role::firstOrCreate(['name' => 'Superadmin']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $memberRole = Role::firstOrCreate(['name' => 'Member']);

        User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password'),
                'role_id' => $superadminRole->id,
                'phone' => null,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role_id' => $memberRole->id,
                'phone' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}
