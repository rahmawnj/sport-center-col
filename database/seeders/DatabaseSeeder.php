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
        $this->call(BookingLandingPageSeeder::class);

        // This project uses its own roles table (name only) and users.role_id.
        $adminRole = Role::firstOrCreate([
            'name' => 'Superadmin',
        ]);

        $admin = User::firstOrNew([
            'email' => 'superadmin@example.com',
        ]);

        $admin->name = 'Superadmin';
        $admin->role_id = $adminRole->id;

        if (!$admin->exists) {
            $admin->password = Hash::make('password');
            $admin->email_verified_at = now();
        }

        $admin->save();
    }
}
