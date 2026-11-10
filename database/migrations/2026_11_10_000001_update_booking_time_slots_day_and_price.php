<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_time_slots', function (Blueprint $table) {
            $table->enum('day', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable()->after('facility_id');
            $table->decimal('price', 12, 2)->default(0)->after('end_time');
        });

        $dayMap = [
            'all' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'weekday' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'weekend' => ['saturday', 'sunday'],
        ];

        foreach (DB::table('booking_time_slots')->orderBy('id')->get() as $slot) {
            $days = $dayMap[$slot->day_type] ?? ['monday'];
            DB::table('booking_time_slots')->where('id', $slot->id)->update(['day' => $days[0]]);

            foreach (array_slice($days, 1) as $day) {
                DB::table('booking_time_slots')->insert([
                    'facility_id' => $slot->facility_id,
                    'day' => $day,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'price' => 0,
                    'is_active' => $slot->is_active,
                    'created_at' => $slot->created_at,
                    'updated_at' => $slot->updated_at,
                ]);
            }
        }

        Schema::table('booking_time_slots', function (Blueprint $table) {
            $table->dropIndex('booking_slots_facility_day_active_start_idx');
            $table->dropColumn('day_type');
            $table->index(['facility_id', 'day', 'is_active', 'start_time'], 'booking_slots_facility_day_active_start_idx');
        });
    }

    public function down(): void
    {
        Schema::table('booking_time_slots', function (Blueprint $table) {
            $table->dropIndex('booking_slots_facility_day_active_start_idx');
            $table->dropColumn(['day', 'price']);
            $table->enum('day_type', ['all', 'weekday', 'weekend'])->default('all');
            $table->index(['facility_id', 'day_type', 'is_active', 'start_time'], 'booking_slots_facility_day_active_start_idx');
        });
    }
};
