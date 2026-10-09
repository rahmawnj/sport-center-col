<?php

namespace Database\Seeders;

use App\Models\Zone;
use App\Models\ZoneSpace;
use App\Models\Facility;
use App\Models\Package;
use App\Models\PackagePricingRule;
use App\Models\Trainer;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SportCenterSeeder extends Seeder
{
    public function run(): void
    {
        $member = User::where('email', 'test@example.com')->first();

        foreach ([
            ['Andi Pratama', 'Personal Trainer Gym', '081234567890'],
            ['Budi Santoso', 'Padel & Functional Training', '081234567891'],
            ['Citra Lestari', 'Yoga & Pilates', '081234567892'],
        ] as [$name, $specialty, $phone]) {
            Trainer::updateOrCreate(
                ['name' => $name],
                ['specialty' => $specialty, 'phone' => $phone]
            );
        }

        $facilities = [
            ['Padel', 'padel', true, 10, ['Padel Court A', 'Padel Court B'], [
                ['Padel 1 Jam', 'session', 60, null, [
                    ['weekday','08:00','16:00',100000,10], ['weekday','16:00','23:00',150000,10],
                    ['weekend','08:00','16:00',125000,10], ['weekend','16:00','23:00',175000,10],
                ]],
                ['Padel Promo Happy Hour', 'session', 60, null, [['all','10:00','14:00',75000,100]]],
            ]],
            ['Billiard', 'billiard', true, 5, ['Billiard Table 1', 'Billiard Table 2', 'Billiard Table 3'], [
                ['Billiard 1 Jam', 'session', 60, null, [['all','10:00','23:00',60000,10]]],
                ['Billiard Promo Siang', 'session', 60, null, [['weekday','12:00','16:00',40000,100]]],
            ]],
            ['Ice Skating', 'ice-skating', true, 10, ['Ice Rink'], [
                ['Ice Skating 1 Jam', 'session', 60, null, [['weekday','10:00','22:00',80000,10],['weekend','09:00','22:00',100000,10]]],
                ['Ice Skating Unlimited 1x Masuk', 'visit', null, null, [['all','09:00','22:00',120000,10]]],
                ['Ice Skating Paket Jam Tertentu', 'session', 120, null, [['all','18:00','20:00',150000,50]]],
            ]],
            ['Gym', 'gym', false, 0, ['Gym Floor'], [
                ['Gym Iuran Bulanan', 'membership', null, 30, [['all',null,null,250000,10]]],
                ['Gym Iuran Harian', 'visit', null, null, [['all',null,null,50000,10]]],
                ['Gym Sesi Trainer 60 Menit', 'session', 60, null, [['all','08:00','22:00',150000,10]]],
            ]],
            ['Yoga', 'yoga', true, 10, ['Yoga Studio'], [
                ['Yoga Member per Sesi', 'session', 60, null, [['weekday','08:00','20:00',75000,10],['weekend','09:00','17:00',90000,10]]],
                ['Yoga Member Paket 8 Sesi', 'package', 60, null, [['all',null,null,500000,10]]],
            ]],
            ['Pilates', 'pilates', true, 10, ['Pilates Studio'], [
                ['Pilates Member per Sesi', 'session', 60, null, [['weekday','08:00','20:00',100000,10],['weekend','09:00','17:00',120000,10]]],
                ['Pilates Member Paket 8 Sesi', 'package', 60, null, [['all',null,null,700000,10]]],
            ]],
            ['Spinning', 'spinning', true, 10, ['Spinning Studio'], [
                ['Spinning 1x Masuk Ruang', 'visit', 60, null, [['all','08:00','21:00',50000,10]]],
            ]],
        ];

        foreach ($facilities as [$name, $slug, $online, $turnaround, $spaces, $packages]) {
            $facility = Facility::updateOrCreate(
                ['name' => $name],
                ['icon' => $slug]
            );

            $pricingModel = match ($slug) {
                'padel' => 'per_space',
                'billiard' => 'per_table',
                'gym' => 'per_person',
                'yoga', 'pilates' => 'per_trainer_session',
                default => 'per_space',
            };

            $zone = Zone::updateOrCreate(
                ['name' => $name],
                ['pricing_model' => $pricingModel, 'is_online_bookable' => $online]
            );

            foreach ($spaces as $spaceName) {
                $space = ZoneSpace::updateOrCreate(
                    ['zone_id' => $zone->id, 'name' => $spaceName],
                    ['capacity' => 1, 'status' => 'available']
                );

                $space->facilities()->syncWithoutDetaching([$facility->id]);
            }

            foreach ($packages as [$packageName, $type, $minutes, $days, $rules]) {
                $package = Package::updateOrCreate(
                    ['facility_id' => $facility->id, 'name' => $packageName],
                    ['type' => $type, 'duration_minutes' => $minutes, 'duration_days' => $days]
                );

                foreach ($rules as [$dayType, $start, $end, $price, $priority]) {
                    PackagePricingRule::updateOrCreate(
                        [
                            'package_id' => $package->id,
                            'day_type' => $dayType,
                            'start_time' => $start,
                            'end_time' => $end,
                        ],
                        [
                            'date_start' => null,
                            'date_end' => null,
                            'price' => $price,
                            'priority' => $priority,
                        ]
                    );
                }
            }
        }

        if ($member) {
            $gym = Package::where('name', 'Gym Iuran Bulanan')->first();

            if ($gym) {
                UserMembership::updateOrCreate(
                    ['user_id' => $member->id, 'package_id' => $gym->id],
                    [
                        'start_date' => Carbon::today(),
                        'end_date' => Carbon::today()->addDays(30),
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
