<?php

namespace Database\Seeders;

use App\Models\MemberTrainerPackage;
use App\Models\Package;
use App\Models\Trainer;
use App\Models\TrainerPackage;
use App\Models\TrainerSessionSchedule;
use App\Models\User;
use App\Models\UserMembership;
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

        $trainerPackages = TrainerPackage::query()
            ->where('is_active', true)
            ->whereHas('facility', fn ($query) => $query
                ->where('status', 'active')
                ->where('has_membership', true))
            ->with('facility')
            ->orderBy('id')
            ->get();

        if ($members->isEmpty() || $trainerPackages->isEmpty()) {
            $this->command?->warn('ActiveMemberTrainerSeeder dilewati: demo member atau paket trainer aktif belum tersedia.');

            return;
        }

        $createdMemberships = 0;
        $createdTrainerPackages = 0;
        $createdSchedules = 0;
        $sessionDate = $today->copy()->addDays(2);

        foreach ($members as $index => $member) {
            $trainerPackage = $trainerPackages[$index % $trainerPackages->count()];

            // Membership harus berasal dari fasilitas yang sama dengan paket trainer.
            // UserMembership.package_id mengarah ke tabel packages, bukan membership_packages.
            $membershipPackage = Package::query()
                ->where('type', 'membership')
                ->where('facility_id', $trainerPackage->facility_id)
                ->whereNotNull('duration_days')
                ->orderBy('id')
                ->first();

            if (! $membershipPackage) {
                $this->command?->warn("Tidak ada paket membership untuk fasilitas {$trainerPackage->facility_id}; data trainer untuk {$member->email} dilewati.");

                continue;
            }

            // Jangan membuat membership duplikat jika member sudah memiliki membership
            // aktif pada fasilitas yang sama dan berlaku hari ini.
            $membership = UserMembership::query()
                ->where('user_id', $member->id)
                ->where('status', 'active')
                ->whereDate('start_date', '<=', $today->toDateString())
                ->whereDate('end_date', '>=', $today->toDateString())
                ->whereHas('package', fn ($query) => $query->where('facility_id', $trainerPackage->facility_id))
                ->orderByDesc('end_date')
                ->first();

            if (! $membership) {
                $membership = UserMembership::firstOrCreate(
                    [
                        'user_id' => $member->id,
                        'package_id' => $membershipPackage->id,
                        'start_date' => $today->toDateString(),
                    ],
                    [
                        'transaction_id' => null,
                        'device_id' => null,
                        'end_date' => $today->copy()
                            ->addDays(max(1, (int) $membershipPackage->duration_days) - 1)
                            ->toDateString(),
                        'status' => 'active',
                    ]
                );

                if ($membership->wasRecentlyCreated) {
                    $createdMemberships++;
                }
            }

            $trainer = Trainer::query()
                ->whereHas('facilities', fn ($query) => $query->whereKey($trainerPackage->facility_id))
                ->orderBy('id')
                ->first();

            if (! $trainer) {
                continue;
            }

            $memberTrainerPackage = MemberTrainerPackage::firstOrCreate(
                [
                    'user_id' => $member->id,
                    'trainer_package_id' => $trainerPackage->id,
                    'trainer_id' => $trainer->id,
                    'start_date' => $today->toDateString(),
                ],
                [
                    'transaction_id' => null,
                    'facility_id' => $trainerPackage->facility_id,
                    'expires_at' => $today->copy()
                        ->addDays(max(1, (int) $trainerPackage->duration_days) - 1)
                        ->toDateString(),
                    'total_sessions' => $trainerPackage->total_sessions,
                    'used_sessions' => 0,
                    'status' => 'active',
                ]
            );

            if ($memberTrainerPackage->wasRecentlyCreated) {
                $createdTrainerPackages++;
            }

            // Buat sesi demo hanya jika membership dan paket trainer masih berlaku
            // pada tanggal sesi yang akan datang.
            if ($membership->end_date->lt($sessionDate)
                || $memberTrainerPackage->expires_at->lt($sessionDate)) {
                continue;
            }

            // Jam dibuat berbeda agar jadwal antar-member tidak saling bentrok.
            $startTime = sprintf('%02d:00:00', 8 + $index);
            $endTime = sprintf('%02d:00:00', 9 + $index);

            $schedule = TrainerSessionSchedule::firstOrCreate(
                [
                    'member_trainer_package_id' => $memberTrainerPackage->id,
                    'session_date' => $sessionDate->toDateString(),
                    'start_time' => $startTime,
                ],
                [
                    'end_time' => $endTime,
                    'status' => 'scheduled',
                    'member_attended' => false,
                    'trainer_attended' => false,
                ]
            );

            if ($schedule->wasRecentlyCreated) {
                $createdSchedules++;
            }
        }

        $this->command?->info(
            "Seeder membership dan personal trainer aktif selesai. "
            . "{$createdMemberships} membership baru, "
            . "{$createdTrainerPackages} pembelian trainer baru, "
            . "{$createdSchedules} jadwal sesi baru."
        );
    }
}
