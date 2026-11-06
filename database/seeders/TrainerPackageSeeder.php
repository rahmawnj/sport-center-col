<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\TrainerPackage;
use Illuminate\Database\Seeder;

class TrainerPackageSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = Facility::query()
            ->where('status', 'active')
            ->where('has_membership', true)
            ->orderBy('id')
            ->get();

        foreach ($facilities as $facility) {
            $packageDefinitions = [
                [
                    'sessions' => 5,
                    'days' => 30,
                    'price' => 500000,
                    'description' => "Paket personal training {$facility->name} sebanyak 5 sesi.",
                ],
                [
                    'sessions' => 10,
                    'days' => 60,
                    'price' => 900000,
                    'description' => "Paket personal training {$facility->name} sebanyak 10 sesi.",
                ],
                [
                    'sessions' => 20,
                    'days' => 90,
                    'price' => 1600000,
                    'description' => "Paket personal training {$facility->name} sebanyak 20 sesi.",
                ],
            ];

            foreach ($packageDefinitions as $definition) {
                $name = "Personal Training {$facility->name} {$definition['sessions']} Sesi";

                $package = TrainerPackage::updateOrCreate(
                    ['name' => $name],
                    [
                        'facility_id' => $facility->id,
                        'total_sessions' => $definition['sessions'],
                        'duration_days' => $definition['days'],
                        'price' => $definition['price'],
                        'description' => $definition['description'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
