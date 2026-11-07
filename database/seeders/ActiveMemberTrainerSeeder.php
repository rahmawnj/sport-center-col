<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\MemberTrainerPackage;
use App\Models\Trainer;
use App\Models\TrainerPackage;
use App\Models\TrainerSessionSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActiveMemberTrainerSeeder extends Seeder
{
    public function run(): void
    {
        $today = today();

        $members = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'Member'))
            ->whereIn('email', [
                'member@example.com',
                'andi.pratama@example.com',
                'siti.rahma@example.com',
                'budi.santoso@example.com',
                'dewi.lestari@example.com',
                'rizky.maulana@example.com',
            ])
            ->orderBy('id')
            ->get();

        $packages = TrainerPackage::query()
            ->where('is_active', true)
            ->whereHas('facility', fn ($query) => $query
                ->where('status', 'active')
                ->where('has_membership', true))
            ->with('facility')
            ->orderBy('id')
            ->get();

        if ($members->isEmpty() || $packages->isEmpty()) {
            $this->command?->warn('ActiveMemberTrainerSeeder dilewati: demo member atau paket trainer aktif belum tersedia.');

            return;
        }

        $created = 0;

        foreach ($members as $index => $member) {
            $package = $packages[$index % $packages->count()];

            $trainer = Trainer::query()
                ->whereHas('facilities', fn ($query) => $query->whereKey($package->facility_id))
                ->orderBy('id')
                ->first();

            if (! $trainer) {
                continue;
            }

            $memberPackage = MemberTrainerPackage::firstOrCreate(
                [
                    'user_id' => $member->id,
                    'trainer_package_id' => $package->id,
                    'trainer_id' => $trainer->id,
                    'start_date' => $today->toDateString(),
                ],
                [
                    'transaction_id' => null,
                    'facility_id' => $package->facility_id,
                    'expires_at' => $today->copy()->addDays(max(1, $package->duration_days) - 1)->toDateString(),
                    'total_sessions' => $package->total_sessions,
                    'used_sessions' => 0,
                    'status' => 'active',
                ]
            );

            if ($memberPackage->wasRecentlyCreated) {
                $created++;
            }

            // Satu jadwal demo mendatang agar riwayat sesi terlihat di halaman detail.
            $sessionDate = $today->copy()->addDays(2)->toDateString();

            TrainerSessionSchedule::firstOrCreate(
                [
                    'member_trainer_package_id' => $memberPackage->id,
                    'session_date' => $sessionDate,
                    'start_time' => '09:00:00',
                ],
                [
                    'end_time' => '10:00:00',
                    'status' => 'scheduled',
                    'member_attended' => false,
                    'trainer_attended' => false,
                ]
            );
        }

        $this->command?->info("Data pembelian personal trainer aktif hari ini siap. {$created} data pembelian baru dibuat.");
    }
}
