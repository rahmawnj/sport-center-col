<?php

namespace Database\Seeders;

use App\Models\BookingTimeSlot;
use App\Models\Facility;
use Illuminate\Database\Seeder;

class BookingTimeSlotSeeder extends Seeder
{
    /**
     * Create default online booking hours for every existing facility.
     *
     * The booking slots themselves are generated dynamically from the
     * package duration and the facility's turnaround_minutes setting.
     */
    public function run(): void
    {
        Facility::query()
            ->where('is_bookable_online', true)
            ->each(function (Facility $facility): void {
                BookingTimeSlot::firstOrCreate(
                    [
                        'facility_id' => $facility->id,
                        'day_type' => 'all',
                        'start_time' => '07:00:00',
                        'end_time' => '22:00:00',
                    ],
                    [
                        'is_active' => true,
                    ],
                );
            });
    }
}
