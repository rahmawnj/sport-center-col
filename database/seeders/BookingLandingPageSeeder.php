<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\Court;
use App\Models\Facility;
use App\Models\Trainer;
use App\Models\Zone;
use App\Models\ZoneSpace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingLandingPageSeeder extends Seeder
{
    /**
     * Seed only the public landing-page and online-booking catalog.
     *
     * The operational-hours records define the opening window. Booking
     * availability should generate hourly start/end slots from that window;
     * each pricing rate is a per-hour price valid during the opening window.
     *
     * Safe to run repeatedly: matching records are updated, not duplicated.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            /*
             * Only sports currently used by the public landing page and
             * online booking flow are included here.
             */
            $facilityData = [
                ['name' => 'Padel', 'slug' => 'padel', 'is_bookable_online' => true],
                ['name' => 'Billiard', 'slug' => 'billiard', 'is_bookable_online' => true],
                ['name' => 'Gym', 'slug' => 'gym', 'is_bookable_online' => true],
                ['name' => 'AC', 'slug' => 'ac', 'is_bookable_online' => false],
                ['name' => 'Free WiFi', 'slug' => 'free-wifi', 'is_bookable_online' => false],
                ['name' => 'Loker', 'slug' => 'loker', 'is_bookable_online' => false],
            ];

            $facilities = collect($facilityData)->mapWithKeys(function (array $data) {
                $facility = Facility::updateOrCreate(
                    ['slug' => $data['slug']],
                    [
                        'name' => $data['name'],
                        'is_bookable_online' => $data['is_bookable_online'],
                        'status' => 'active',
                    ]
                );

                return [$data['slug'] => $facility];
            });

            /*
             * Each sport has one hourly rate and its own opening window.
             * day_of_week NULL means the rate applies every day.
             */
            $sports = [
                [
                    'name' => 'Padel',
                    'pricing_model' => 'per_space',
                    'open_time' => '08:00:00',
                    'close_time' => '23:00:00',
                    'spaces' => [
                        ['name' => 'Padel Court A', 'capacity' => 4, 'price' => 150000],
                        ['name' => 'Padel Court B', 'capacity' => 4, 'price' => 150000],
                    ],
                ],
                [
                    'name' => 'Billiard',
                    'pricing_model' => 'per_table',
                    'open_time' => '10:00:00',
                    'close_time' => '23:00:00',
                    'spaces' => [
                        ['name' => 'Billiard Table 1', 'capacity' => 4, 'price' => 50000],
                        ['name' => 'Billiard Table 2', 'capacity' => 4, 'price' => 50000],
                    ],
                ],
                [
                    'name' => 'Gym',
                    'pricing_model' => 'per_person',
                    'open_time' => '06:00:00',
                    'close_time' => '22:00:00',
                    'spaces' => [
                        ['name' => 'Gym Floor', 'capacity' => 20, 'price' => 35000],
                    ],
                ],
            ];

            foreach ($sports as $sportData) {
                $zone = Zone::updateOrCreate(
                    ['name' => $sportData['name']],
                    [
                        'pricing_model' => $sportData['pricing_model'],
                        'is_online_bookable' => true,
                    ]
                );

                /*
                 * Schema comment defines 1=Monday through 7=Sunday.
                 * Seed every day so the booking module has a complete weekly
                 * schedule and can generate one-hour slots from open to close.
                 */
                foreach (range(1, 7) as $dayOfWeek) {
                    DB::table('operational_hours')->updateOrInsert(
                        [
                            'zone_id' => $zone->id,
                            'day_of_week' => $dayOfWeek,
                        ],
                        [
                            'open_time' => $sportData['open_time'],
                            'close_time' => $sportData['close_time'],
                            'is_closed' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                foreach ($sportData['spaces'] as $spaceData) {
                    $space = ZoneSpace::updateOrCreate(
                        [
                            'zone_id' => $zone->id,
                            'name' => $spaceData['name'],
                        ],
                        [
                            'capacity' => $spaceData['capacity'],
                            'status' => 'available',
                        ]
                    );

                    // Associate the sport itself and its amenities with the space.
                    foreach (array_merge(
                        [$sportData['name'] === 'Padel' ? 'padel' : ($sportData['name'] === 'Billiard' ? 'billiard' : 'gym')],
                        ['ac', 'free-wifi', 'loker']
                    ) as $facilitySlug) {
                        DB::table('facility_zone_space')->updateOrInsert(
                            [
                                'zone_space_id' => $space->id,
                                'facility_id' => $facilities[$facilitySlug]->id,
                            ],
                            [
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }

                    /*
                     * Use the existing pricing_rates columns from the current
                     * schema. A single per_hour rate covers the opening window;
                     * available hourly booking slots are derived from
                     * operational_hours rather than hard-coded as packages.
                     *
                     * DB::table is intentional because the PricingRate model's
                     * fillable list does not include the schedule fields.
                     */
                    DB::table('pricing_rates')->updateOrInsert(
                        [
                            'zone_space_id' => $space->id,
                            'rental_type' => 'Reguler',
                        ],
                        [
                            'trainer_id' => null,
                            'price' => $spaceData['price'],
                            'unit_type' => 'per_hour',
                            'min_booking_duration' => 1,
                            'day_of_week' => null,
                            'start_time' => $sportData['open_time'],
                            'end_time' => $sportData['close_time'],
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                // The booking flow uses a Court record as the reservable unit for each sport.
                if (in_array($sportData['name'], ['Padel', 'Billiard', 'Gym'], true)) {
                    foreach ($sportData['spaces'] as $spaceData) {
                        Court::updateOrCreate(
                            [
                                'facility_id' => $facilities[strtolower($sportData['name'])]->id,
                                'name' => $spaceData['name'],
                            ],
                            ['status' => 'available']
                        );
                    }
                }
            }

            /*
             * Public booking uses the current Facility -> Package -> PricingRule
             * schema. Keep it in sync with the hourly catalog above so the
             * landing page and booking wizard read the same prices and hours.
             */
            foreach ($sports as $sportData) {
                $facility = $facilities[strtolower($sportData['name'])];

                $package = \\App\\Models\\Package::updateOrCreate(
                    [
                        'facility_id' => $facility->id,
                        'name' => 'Sewa ' . $sportData['name'] . ' per Jam',
                    ],
                    [
                        'type' => 'session',
                        'duration_minutes' => 60,
                        'duration_days' => null,
                    ]
                );

                \\App\\Models\\PackagePricingRule::updateOrCreate(
                    [
                        'package_id' => $package->id,
                        'day_type' => 'all',
                        'start_time' => substr($sportData['open_time'], 0, 5),
                        'end_time' => substr($sportData['close_time'], 0, 5),
                    ],
                    [
                        'date_start' => null,
                        'date_end' => null,
                        'price' => $sportData['spaces'][0]['price'],
                        'priority' => 0,
                    ]
                );
            }

            /*
             * Booking add-ons. updateOrCreate keeps catalog data consistent
             * when rerun; stock is reset to the configured seed stock.
             */
            foreach ([
                ['name' => 'Raket Padel', 'price' => 35000, 'stock' => 20],
                ['name' => 'Handuk', 'price' => 10000, 'stock' => 50],
                ['name' => 'Bola Billiard Tambahan', 'price' => 15000, 'stock' => 5],
            ] as $addOnData) {
                AddOn::updateOrCreate(
                    ['name' => $addOnData['name']],
                    [
                        'price' => $addOnData['price'],
                        'stock' => $addOnData['stock'],
                    ]
                );
            }

            /*
             * Trainer profiles only need name and specialty. The current
             * database migration removes trainers.phone, so do not reference it.
             */
            foreach ([
                ['name' => 'Coach Padel 1', 'specialty' => 'Padel'],
                ['name' => 'Coach Gym 1', 'specialty' => 'Personal Training'],
            ] as $trainerData) {
                Trainer::updateOrCreate(
                    ['name' => $trainerData['name']],
                    ['specialty' => $trainerData['specialty']]
                );
            }
        });
    }
}
