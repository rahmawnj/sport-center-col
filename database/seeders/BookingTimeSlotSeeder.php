<?php

namespace Database\Seeders;

use App\Models\BookingTimeSlot;
use App\Models\Facility;
use Illuminate\Database\Seeder;

class BookingTimeSlotSeeder extends Seeder
{
    /**
     * Seed weekday and weekend operating windows for every online sport.
     * Booking slots are generated dynamically from package duration and
     * each facility's turnaround_minutes setting.
     */
    public function run(): void
    {
        Facility::query()
            ->where('is_bookable_online', true)
            ->each(function (Facility $facility): void {
                // Remove only the previous default row created by this seeder.
                BookingTimeSlot::query()
                    ->where('facility_id', $facility->id)
                    ->where('day_type', 'all')
                    ->whereTime('start_time', '07:00:00')
                    ->whereTime('end_time', '22:00:00')
                    ->delete();

                foreach (['weekday', 'weekend'] as $dayType) {
                    BookingTimeSlot::updateOrCreate(
                        [
                            'facility_id' => $facility->id,
                            'day_type' => $dayType,
                            'start_time' => '07:00:00',
                            'end_time' => '23:00:00',
                        ],
                        [
                            'is_active' => true,
                        ],
                    );
                }
            });
    }
}
