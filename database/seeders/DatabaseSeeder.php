<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BookingLandingPageSeeder::class);

        // The users table requires role_id, so create the role first.
        $adminRole = Role::firstOrCreate([
            'name' => 'Superadmin',
            'guard_name' => 'web',
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

        // Also assign the Spatie role for permission checks.
        if (!$admin->hasRole($adminRole->name)) {
            $admin->assignRole($adminRole);
        }
    }
}
