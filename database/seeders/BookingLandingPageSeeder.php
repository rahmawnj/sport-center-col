<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\PricingRate;
use App\Models\Zone;
use App\Models\ZoneSpace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingLandingPageSeeder extends Seeder
{
    /**
     * Seed only the public landing page and online booking catalog.
     *
     * Prices below are starter/demo prices and can be adjusted in the admin.
     */
    public function run(): void
    {
        $amenities = collect([
            ['name' => 'AC', 'slug' => 'ac'],
            ['name' => 'Free WiFi', 'slug' => 'free-wifi'],
            ['name' => 'Loker', 'slug' => 'loker'],
        ])->mapWithKeys(function (array $data) {
            $facility = Facility::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'is_bookable_online' => false,
                    'status' => 'active',
                ]
            );

            return [$data['slug'] => $facility];
        });

        $catalog = [
            [
                'name' => 'Padel',
                'pricing_model' => 'per_space',
                'spaces' => [
                    ['name' => 'Padel Court A', 'capacity' => 4, 'price' => 150000],
                    ['name' => 'Padel Court B', 'capacity' => 4, 'price' => 150000],
                ],
            ],
            [
                'name' => 'Billiard',
                'pricing_model' => 'per_table',
                'spaces' => [
                    ['name' => 'Billiard Table 1', 'capacity' => 4, 'price' => 50000],
                    ['name' => 'Billiard Table 2', 'capacity' => 4, 'price' => 50000],
                ],
            ],
            [
                'name' => 'Gym',
                'pricing_model' => 'per_person',
                'spaces' => [
                    ['name' => 'Gym Floor', 'capacity' => 20, 'price' => 35000],
                ],
            ],
        ];

        foreach ($catalog as $zoneData) {
            $zone = Zone::updateOrCreate(
                ['name' => $zoneData['name']],
                [
                    'pricing_model' => $zoneData['pricing_model'],
                    'is_online_bookable' => true,
                ]
            );

            foreach ($zoneData['spaces'] as $spaceData) {
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

                // The pivot table has a composite primary key, so attach
                // amenities only when the relationship is not already present.
                foreach ($amenities as $amenity) {
                    DB::table('facility_zone_space')->updateOrInsert(
                        [
                            'zone_space_id' => $space->id,
                            'facility_id' => $amenity->id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                PricingRate::updateOrCreate(
                    [
                        'zone_space_id' => $space->id,
                        'rental_type' => 'Reguler',
                    ],
                    [
                        'price' => $spaceData['price'],
                        'unit_type' => 'per_hour',
                        'min_booking_duration' => 1,
                    ]
                );
            }
        }
    }
}
