<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Role;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TrainerSeeder extends Seeder
{
    public function run(): void
    {
        $trainerRole = Role::firstOrCreate(['name' => 'Trainer']);

        $facilities = Facility::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if (empty($facilities)) {
            $this->command?->warn('Trainer seeder dilewati karena belum ada olahraga aktif.');

            return;
        }


        $trainers = [
            ['name' => 'Alex Wijaya', 'email' => 'alex.wijaya@trainer.test'],
            ['name' => 'Bima Saputra', 'email' => 'bima.saputra@trainer.test'],
            ['name' => 'Citra Maharani', 'email' => 'citra.maharani@trainer.test'],
            ['name' => 'Dimas Pratama', 'email' => 'dimas.pratama@trainer.test'],
            ['name' => 'Eka Putri', 'email' => 'eka.putri@trainer.test'],
            ['name' => 'Farhan Ramadhan', 'email' => 'farhan.ramadhan@trainer.test'],
            ['name' => 'Gita Anggraini', 'email' => 'gita.anggraini@trainer.test'],
            ['name' => 'Hendra Kurniawan', 'email' => 'hendra.kurniawan@trainer.test'],
        ];

        foreach ($trainers as $index => $data) {
            $user = User::firstOrNew(['email' => $data['email']]);
            $user->name = $data['name'];
            $user->role_id = $trainerRole->id;
            $user->password = Hash::make('password');
            $user->email_verified_at ??= now();
            $user->save();

            $trainer = Trainer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $data['name'],
                    'specialty' => 'Personal Trainer',
                ]
            );

            // Satu trainer mendapat tepat satu olahraga secara konsisten.
            $facilityId = $facilities[$index % count($facilities)];
            $trainer->facilities()->sync([$facilityId]);
        }
    }
}
