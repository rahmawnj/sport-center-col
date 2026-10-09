<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\Court;
use App\Models\Facility;
use App\Models\PricingRate;
use App\Models\Trainer;
use App\Models\Zone;
use App\Models\ZoneSpace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingLandingPageSeeder extends Seeder
{
    /**
     * Seed the public landing page and online booking catalog.
     *
     * Prices are starter/demo values and can be adjusted in the admin.
     * Safe to run repeatedly: records are updated instead of duplicated.
     */
    public function run(): void
    {
        /*
         * Sports facilities are the parent records for courts.
         * Amenities are also stored in facilities because the existing
         * facility_zone_space pivot uses facility_id.
         */
        $facilityData = [
            ['name' => 'Padel', 'slug' => 'padel', 'is_bookable_online' => true, 'status' => 'active'],
            ['name' => 'Billiard', 'slug' => 'billiard', 'is_bookable_online' => true, 'status' => 'active'],
            ['name' => 'Gym', 'slug' => 'gym', 'is_bookable_online' => true, 'status' => 'active'],
            ['name' => 'AC', 'slug' => 'ac', 'is_bookable_online' => false, 'status' => 'active'],
            ['name' => 'Free WiFi', 'slug' => 'free-wifi', 'is_bookable_online' => false, 'status' => 'active'],
            ['name' => 'Loker', 'slug' => 'loker', 'is_bookable_online' => false, 'status' => 'active'],
        ];

        $facilities = collect($facilityData)->mapWithKeys(function (array $data) {
            $facility = Facility::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'is_bookable_online' => $data['is_bookable_online'],
                    'status' => $data['status'],
                ]
            );

            return [$data['slug'] => $facility];
        });

        /*
         * Courts are used by the booking model, while zone spaces are used
         * by the public catalog and its hourly pricing.
         */
        $courts = [
            ['facility' => 'padel', 'name' => 'Padel Court A'],
            ['facility' => 'padel', 'name' => 'Padel Court B'],
            ['facility' => 'billiard', 'name' => 'Billiard Table 1'],
            ['facility' => 'billiard', 'name' => 'Billiard Table 2'],
        ];

        foreach ($courts as $courtData) {
            Court::updateOrCreate(
                [
                    'facility_id' => $facilities[$courtData['facility']]->id,
                    'name' => $courtData['name'],
                ],
                ['status' => 'available']
            );
        }

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

                foreach (['ac', 'free-wifi', 'loker'] as $amenitySlug) {
                    DB::table('facility_zone_space')->updateOrInsert(
                        [
                            'zone_space_id' => $space->id,
                            'facility_id' => $facilities[$amenitySlug]->id,
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

        /*
         * Booking add-ons. Prices are examples in Indonesian rupiah.
         */
        $addOns = [
            ['name' => 'Raket Padel', 'price' => 35000, 'stock' => 20],
            ['name' => 'Handuk', 'price' => 10000, 'stock' => 50],
            ['name' => 'Bola Billiard Tambahan', 'price' => 15000, 'stock' => 5],
        ];

        foreach ($addOns as $addOnData) {
            AddOn::updateOrCreate(
                ['name' => $addOnData['name']],
                [
                    'price' => $addOnData['price'],
                    'stock' => $addOnData['stock'],
                ]
            );
        }

        /*
         * Example trainer profiles for the landing page.
         * Replace these names and phone numbers with real staff details.
         */
        $trainers = [
            ['name' => 'Coach Padel 1', 'specialty' => 'Padel', 'phone' => null],
            ['name' => 'Coach Gym 1', 'specialty' => 'Personal Training', 'phone' => null],
        ];

        foreach ($trainers as $trainerData) {
            Trainer::updateOrCreate(
                [
                    'name' => $trainerData['name'],
                    'specialty' => $trainerData['specialty'],
                ],
                ['phone' => $trainerData['phone']]
            );
        }
    }
}
