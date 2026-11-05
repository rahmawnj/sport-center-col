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

        // Acak urutan olahraga, tetapi pastikan setiap olahraga mendapat trainer
        // selama jumlah trainer mencukupi.
        shuffle($facilities);

        $trainers = [
            ['name' => 'Alex Wijaya', 'email' => 'alex.wijaya@trainer.test', 'phone' => '081200000101'],
            ['name' => 'Bima Saputra', 'email' => 'bima.saputra@trainer.test', 'phone' => '081200000102'],
            ['name' => 'Citra Maharani', 'email' => 'citra.maharani@trainer.test', 'phone' => '081200000103'],
            ['name' => 'Dimas Pratama', 'email' => 'dimas.pratama@trainer.test', 'phone' => '081200000104'],
            ['name' => 'Eka Putri', 'email' => 'eka.putri@trainer.test', 'phone' => '081200000105'],
            ['name' => 'Farhan Ramadhan', 'email' => 'farhan.ramadhan@trainer.test', 'phone' => '081200000106'],
            ['name' => 'Gita Anggraini', 'email' => 'gita.anggraini@trainer.test', 'phone' => '081200000107'],
            ['name' => 'Hendra Kurniawan', 'email' => 'hendra.kurniawan@trainer.test', 'phone' => '081200000108'],
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
                    'phone' => $data['phone'],
                ]
            );

            // Satu trainer mendapat tepat satu olahraga secara acak.
            $facilityId = $facilities[$index % count($facilities)];
            $trainer->facilities()->sync([$facilityId]);
        }
    }
}
