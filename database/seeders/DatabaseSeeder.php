<?php

namespace Database\Seeders;

use App\Models\MemberProfile;
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
        $this->call([
            BookingLandingPageSeeder::class,
            MembershipPackageSeeder::class,
        ]);

        // This project uses its own roles table (name only) and users.role_id.
        $adminRole = Role::firstOrCreate([
            'name' => 'Superadmin',
        ]);

        $admin = User::firstOrNew([
            'email' => 'superadmin@example.com',
        ]);

        $admin->name = 'Superadmin';
        $admin->role_id = $adminRole->id;

        if (! $admin->exists) {
            $admin->password = Hash::make('password');
            $admin->email_verified_at = now();
        }

        $admin->save();

        // Demo member accounts for testing member pages and Register Face.
        $memberRole = Role::firstOrCreate([
            'name' => 'Member',
        ]);

        $members = [
            ['name' => 'Demo Member', 'email' => 'member@example.com', 'phone' => '081234567890'],
            ['name' => 'Andi Pratama', 'email' => 'andi.pratama@example.com', 'phone' => '081234567891'],
            ['name' => 'Siti Rahma', 'email' => 'siti.rahma@example.com', 'phone' => '081234567892'],
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@example.com', 'phone' => '081234567893'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi.lestari@example.com', 'phone' => '081234567894'],
            ['name' => 'Rizky Maulana', 'email' => 'rizky.maulana@example.com', 'phone' => '081234567895'],
            ['name' => 'Putri Amelia', 'email' => 'putri.amelia@example.com', 'phone' => '081234567896'],
            ['name' => 'Fajar Nugraha', 'email' => 'fajar.nugraha@example.com', 'phone' => '081234567897'],
            ['name' => 'Nadia Permata', 'email' => 'nadia.permata@example.com', 'phone' => '081234567898'],
            ['name' => 'Yoga Saputra', 'email' => 'yoga.saputra@example.com', 'phone' => '081234567899'],
        ];

        foreach ($members as $memberData) {
            $member = User::firstOrNew([
                'email' => $memberData['email'],
            ]);

            $member->name = $memberData['name'];
            $member->role_id = $memberRole->id;
            $member->phone = $memberData['phone'];

            if (! $member->exists) {
                $member->password = Hash::make('password');
                $member->email_verified_at = now();
            }

            $member->save();

            // WithoutModelEvents disables MemberProfile's creating hook,
            // so explicitly generate the identifiers here when they are missing.
            $profile = MemberProfile::firstOrNew([
                'user_id' => $member->id,
            ]);

            $profile->phone ??= $memberData['phone'];
            $profile->member_code ??= MemberProfile::makeMemberCode();
            $profile->qr_code ??= MemberProfile::makeQrCode();
            $profile->save();
        }
    }
}
