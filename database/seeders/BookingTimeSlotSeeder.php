<?php

namespace Database\Seeders;

use App\Models\BookingTimeSlot;
use App\Models\Facility;
use Illuminate\Database\Seeder;

class BookingTimeSlotSeeder extends Seeder
{
    private const DAYS = [
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday',
        'saturday', 'sunday',
    ];

    public function run(): void
    {
        Facility::query()
            ->where('is_bookable_online', true)
            ->each(function (Facility $facility): void {
                $basePrice = $this->basePriceFor($facility->name);

                // Remove only legacy rows migrated without a price.
                BookingTimeSlot::query()
                    ->where('facility_id', $facility->id)
                    ->where('price', 0)
                    ->delete();

                foreach (self::DAYS as $day) {
                    $weekend = in_array($day, ['saturday', 'sunday'], true);
                    $multiplier = $weekend ? 1.25 : 1;

                    $periods = [
                        ['07:00', '12:00', 1.00],
                        ['12:00', '17:00', 1.20],
                        ['17:00', '23:00', 1.50],
                    ];

                    foreach ($periods as [$start, $end, $periodMultiplier]) {
                        BookingTimeSlot::updateOrCreate(
                            [
                                'facility_id' => $facility->id,
                                'day' => $day,
                                'start_time' => $start,
                                'end_time' => $end,
                            ],
                            [
                                'price' => (int) (round(($basePrice * $multiplier * $periodMultiplier) / 1000) * 1000),
                                'is_active' => true,
                            ],
                        );
                    }
                }
            });
    }

    private function basePriceFor(string $facilityName): int
    {
        $name = strtolower($facilityName);

        return match (true) {
            str_contains($name, 'badminton') => 50000,
            str_contains($name, 'padel') => 150000,
            str_contains($name, 'futsal') => 120000,
            str_contains($name, 'tennis') => 100000,
            str_contains($name, 'basket') => 100000,
            str_contains($name, 'voli') => 90000,
            str_contains($name, 'renang') => 75000,
            default => 75000,
        };
    }
}
