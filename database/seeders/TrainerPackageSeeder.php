<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Trainer;
use App\Models\TrainerPackage;
use Illuminate\Database\Seeder;

class TrainerPackageSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = Facility::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        foreach ($facilities as $facility) {
            $eligibleTrainers = Trainer::query()
                ->whereHas('facilities', fn ($query) => $query->where('facilities.id', $facility->id))
                ->orderBy('name')
                ->get();

            if ($eligibleTrainers->isEmpty()) {
                $this->command?->warn("Tidak ada trainer untuk olahraga {$facility->name}; paket trainer dilewati.");

                continue;
            }

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

                // Setiap paket wajib memiliki tepat satu olahraga. Beberapa trainer
                // boleh ditugaskan, tetapi semuanya harus menguasai olahraga tersebut.
                $package->trainers()->sync(
                    $eligibleTrainers->take(2)->pluck('id')->all()
                );
            }
        }
    }
}
