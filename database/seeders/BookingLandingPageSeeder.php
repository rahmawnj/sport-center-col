<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\Device;
use App\Models\Facility;
use App\Models\Package;
use App\Models\PackagePricingRule;
use App\Models\Trainer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BookingLandingPageSeeder extends Seeder
{
    /**
     * Seed the existing booking catalog only. This deliberately does not
     * create/alter tables; entry control rules for tripod/face/Qris belong
     * to application logic and cannot be enforced by catalog seed data alone.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $facilityData = [
                ['name' => 'Padel', 'slug' => 'padel', 'online' => true, 'turnaround_minutes' => 5],
                ['name' => 'Billiard', 'slug' => 'billiard', 'online' => true, 'turnaround_minutes' => 5],
                ['name' => 'Gym', 'slug' => 'gym', 'online' => true, 'turnaround_minutes' => 5],
                ['name' => 'Ice Skating', 'slug' => 'ice-skating', 'online' => true, 'turnaround_minutes' => 5],
                ['name' => 'Yoga', 'slug' => 'yoga', 'online' => true, 'turnaround_minutes' => 10],
                ['name' => 'Pilates', 'slug' => 'pilates', 'online' => true, 'turnaround_minutes' => 10],
                ['name' => 'Spinning', 'slug' => 'spinning', 'online' => true, 'turnaround_minutes' => 10],
            ];

            // Amenities belong to a room (court), not to the sport itself.
            $amenitiesBySport = [
                'padel' => ['AC', 'Free WiFi', 'Loker'],
                'billiard' => ['AC', 'Free WiFi'],
                'gym' => ['AC', 'Free WiFi', 'Loker'],
                'ice-skating' => ['Loker'],
                'yoga' => ['AC', 'Free WiFi', 'Loker'],
                'pilates' => ['AC', 'Free WiFi', 'Loker'],
                'spinning' => ['AC', 'Loker'],
            ];

            // Remove legacy amenity rows that older seeds stored as facilities.
            Facility::query()
                ->whereIn('slug', ['ac', 'free-wifi', 'loker'])
                ->whereDoesntHave('courts')
                ->whereDoesntHave('packages')
                ->delete();

            $facilities = collect($facilityData)->mapWithKeys(function (array $data) {
                $attributes = [
                    'name' => $data['name'],
                    'is_bookable_online' => $data['online'],
                    'status' => 'active',
                ];

                // Support existing databases that do not have this optional column.
                if (Schema::hasColumn('facilities', 'turnaround_minutes')) {
                    $attributes['turnaround_minutes'] = $data['turnaround_minutes'];
                }

                $facility = Facility::updateOrCreate(
                    ['slug' => $data['slug']],
                    $attributes
                );

                return [$data['slug'] => $facility];
            });

            // Each price rule is [day_type, start_time, end_time, price, priority].
            $sports = [
                [
                    'name' => 'Padel', 'slug' => 'padel', 'model' => 'per_space',
                    'open' => '08:00:00', 'close' => '23:00:00',
                    'spaces' => [
                        ['name' => 'Padel Court A', 'capacity' => 4],
                        ['name' => 'Padel Court B', 'capacity' => 4],
                    ],
                    'packages' => [
                        ['name' => 'Sewa Padel per Jam', 'type' => 'session', 'minutes' => 60, 'days' => null,
                            'rules' => [
                                ['weekday', '08:00', '16:00', 100000, 10],
                                ['weekday', '16:00', '23:00', 150000, 10],
                                ['weekend', '08:00', '16:00', 125000, 10],
                                ['weekend', '16:00', '23:00', 175000, 10],
                            ]],
                        ['name' => 'Padel Promo Happy Hour', 'type' => 'session', 'minutes' => 60, 'days' => null,
                            'rules' => [['all', '10:00', '14:00', 75000, 100]]],
                    ],
                ],
                [
                    'name' => 'Billiard', 'slug' => 'billiard', 'model' => 'per_table',
                    'open' => '10:00:00', 'close' => '23:00:00',
                    'spaces' => [
                        ['name' => 'Billiard Table 1', 'capacity' => 4],
                        ['name' => 'Billiard Table 2', 'capacity' => 4],
                    ],
                    'packages' => [
                        ['name' => 'Billiard per Jam', 'type' => 'session', 'minutes' => 60, 'days' => null,
                            'rules' => [['all', '10:00', '23:00', 50000, 10]]],
                        ['name' => 'Billiard Promo Siang', 'type' => 'session', 'minutes' => 60, 'days' => null,
                            'rules' => [['weekday', '12:00', '16:00', 35000, 100]]],
                    ],
                ],
                [
                    'name' => 'Gym', 'slug' => 'gym', 'model' => 'per_person',
                    'open' => '06:00:00', 'close' => '22:00:00',
                    'spaces' => [['name' => 'Gym Floor', 'capacity' => 20]],
                    'packages' => [
                        ['name' => 'Gym Iuran Bulanan', 'type' => 'membership', 'minutes' => 60, 'days' => 30,
                            'rules' => [['all', null, null, 250000, 10]]],
                        ['name' => 'Gym Iuran Harian', 'type' => 'entry', 'minutes' => 60, 'days' => null,
                            'rules' => [['all', null, null, 50000, 10]]],
                        ['name' => 'Gym Sesi Trainer 60 Menit', 'type' => 'session', 'minutes' => 60, 'days' => null,
                            'rules' => [['all', '06:00', '22:00', 150000, 10]]],
                        ['name' => 'Gym Akses Jam Tertentu Bulanan', 'type' => 'membership', 'minutes' => 60, 'days' => 30,
                            'rules' => [['all', '06:00', '10:00', 180000, 20]]],
                        ['name' => 'Gym Akses Jam Tertentu Harian', 'type' => 'entry', 'minutes' => 60, 'days' => null,
                            'rules' => [['all', '06:00', '10:00', 30000, 20]]],
                    ],
                ],
                [
                    'name' => 'Ice Skating', 'slug' => 'ice-skating', 'model' => 'per_space',
                    'open' => '09:00:00', 'close' => '22:00:00',
                    'spaces' => [['name' => 'Ice Rink', 'capacity' => 30]],
                    'packages' => [
                        ['name' => 'Ice Skating per Jam', 'type' => 'session', 'minutes' => 60, 'days' => null,
                            'rules' => [
                                ['weekday', '10:00', '22:00', 80000, 10],
                                ['weekend', '09:00', '22:00', 100000, 10],
                            ]],
                        ['name' => 'Ice Skating Unlimited 1x Masuk', 'type' => 'entry', 'minutes' => 60, 'days' => null,
                            'rules' => [['all', '09:00', '22:00', 120000, 20]]],
                        ['name' => 'Ice Skating Paket Jam Tertentu', 'type' => 'session', 'minutes' => 120, 'days' => null,
                            'rules' => [['all', '18:00', '20:00', 150000, 50]]],
                    ],
                ],
                [
                    'name' => 'Yoga', 'slug' => 'yoga', 'model' => 'per_trainer_session',
                    'open' => '08:00:00', 'close' => '20:00:00',
                    'spaces' => [['name' => 'Yoga Studio', 'capacity' => 20]],
                    'packages' => [
                        ['name' => 'Yoga Member per Sesi', 'type' => 'session', 'minutes' => 60, 'days' => null,
                            'rules' => [
                                ['weekday', '08:00', '20:00', 75000, 10],
                                ['weekend', '09:00', '17:00', 90000, 10],
                            ]],
                        ['name' => 'Yoga Member Paket 8 Sesi', 'type' => 'membership', 'minutes' => 60, 'days' => 30,
                            'rules' => [['all', null, null, 500000, 10]]],
                    ],
                ],
                [
                    'name' => 'Pilates', 'slug' => 'pilates', 'model' => 'per_trainer_session',
                    'open' => '08:00:00', 'close' => '20:00:00',
                    'spaces' => [['name' => 'Pilates Studio', 'capacity' => 12]],
                    'packages' => [
                        ['name' => 'Pilates Member per Sesi', 'type' => 'session', 'minutes' => 60, 'days' => null,
                            'rules' => [
                                ['weekday', '08:00', '20:00', 100000, 10],
                                ['weekend', '09:00', '17:00', 120000, 10],
                            ]],
                        ['name' => 'Pilates Member Paket 8 Sesi', 'type' => 'membership', 'minutes' => 60, 'days' => 30,
                            'rules' => [['all', null, null, 700000, 10]]],
                    ],
                ],
                [
                    'name' => 'Spinning', 'slug' => 'spinning', 'model' => 'per_space',
                    'open' => '08:00:00', 'close' => '21:00:00',
                    'spaces' => [['name' => 'Spinning Studio', 'capacity' => 20]],
                    'packages' => [
                        ['name' => 'Spinning 1x Masuk Ruang', 'type' => 'entry', 'minutes' => 60, 'days' => null,
                            'rules' => [['all', '08:00', '21:00', 50000, 10]]],
                    ],
                ],
            ];

            foreach ($sports as $sport) {
                // The booking wizard uses courts as bookable units.
                foreach ($sport['spaces'] as $spaceData) {
                    Court::updateOrCreate(
                        ['facility_id' => $facilities[$sport['slug']]->id, 'name' => $spaceData['name']],
                        [
                            'status' => 'available',
                            'amenities' => $amenitiesBySport[$sport['slug']] ?? [],
                        ]
                    );
                }

                // Reconcile stale court rows from older seed data per sport.
                $expectedCourtNames = collect($sport['spaces'])->pluck('name')->all();
                $staleCourts = Court::query()
                    ->where('facility_id', $facilities[$sport['slug']]->id)
                    ->whereNotIn('name', $expectedCourtNames);
                (clone $staleCourts)->whereDoesntHave('bookings')->delete();
                $staleCourts->whereHas('bookings')->update(['status' => 'inactive']);

                foreach ($sport['packages'] as $packageData) {
                    $package = Package::updateOrCreate(
                        ['facility_id' => $facilities[$sport['slug']]->id, 'name' => $packageData['name']],
                        [
                            'type' => $packageData['type'],
                            'duration_minutes' => $packageData['minutes'] ?? 60,
                            'duration_days' => $packageData['days'],
                        ]
                    );

                    foreach ($packageData['rules'] as [$dayType, $start, $end, $price, $priority]) {
                        PackagePricingRule::updateOrCreate(
                            [
                                'package_id' => $package->id,
                                'day_type' => $dayType,
                                'start_time' => $start,
                                'end_time' => $end,
                                'priority' => $priority,
                            ],
                            [
                                'date_start' => null,
                                'date_end' => null,
                                'price' => $price,
                            ]
                        );
                    }
                }
            }

            // Controllers: one per room for per-court sports, one per venue otherwise.
            $devicePlan = [
                'padel' => ['per_court' => true],
                'billiard' => ['per_court' => true],
                'gym' => ['per_court' => false],
                'ice-skating' => ['per_court' => false],
            ];

            foreach ($devicePlan as $slug => $plan) {
                $facility = $facilities[$slug] ?? null;

                if (! $facility) {
                    continue;
                }

                $prefix = strtoupper(str_replace('-', '', $slug));

                if ($plan['per_court']) {
                    $courts = Court::query()
                        ->where('facility_id', $facility->id)
                        ->orderBy('name')
                        ->get();

                    foreach ($courts as $index => $court) {
                        $code = $prefix.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

                        Device::updateOrCreate(
                            ['device_code' => $code],
                            [
                                'facility_id' => $facility->id,
                                'court_id' => $court->id,
                                'name' => $court->name.' Controller',
                                'type' => 'controller',
                                'mqtt_topic' => 'sportcenter/'.$code.'/command',
                                'status' => 'active',
                            ]
                        );
                    }

                    continue;
                }

                $code = $prefix.'-01';

                Device::updateOrCreate(
                    ['device_code' => $code],
                    [
                        'facility_id' => $facility->id,
                        'court_id' => null,
                        'name' => $facility->name.' Controller',
                        'type' => 'controller',
                        'mqtt_topic' => 'sportcenter/'.$code.'/command',
                        'status' => 'active',
                    ]
                );
            }

            foreach ([
                ['name' => 'Coach Padel 1', 'specialty' => 'Padel'],
                ['name' => 'Coach Gym 1', 'specialty' => 'Personal Training'],
                ['name' => 'Instruktur Yoga', 'specialty' => 'Yoga'],
                ['name' => 'Instruktur Pilates', 'specialty' => 'Pilates'],
                ['name' => 'Instruktur Spinning', 'specialty' => 'Spinning'],
            ] as $trainer) {
                Trainer::updateOrCreate(
                    ['name' => $trainer['name']],
                    ['specialty' => $trainer['specialty']]
                );
            }
        });
    }
}
