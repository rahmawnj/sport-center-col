<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->enum('day', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->default('monday')->after('trainer_id');
            $table->boolean('is_active')->default(true)->after('price');
        });

        // Preserve the weekday and active state of existing one-off schedules.
        foreach (DB::table('class_schedules')->select('id', 'session_date', 'status')->get() as $schedule) {
            $day = strtolower(date('l', strtotime($schedule->session_date)));
            DB::table('class_schedules')->where('id', $schedule->id)->update([
                'day' => $day,
                'is_active' => $schedule->status === 'cancelled' ? false : true,
            ]);
        }

        Schema::table('session_participants', function (Blueprint $table) {
            $table->date('session_date')->nullable()->after('class_schedule_id');
        });

        DB::table('session_participants')
            ->join('class_schedules', 'session_participants.class_schedule_id', '=', 'class_schedules.id')
            ->update(['session_participants.session_date' => DB::raw('class_schedules.session_date')]);

        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropUnique(['class_schedule_id', 'user_id']);
            $table->date('session_date')->nullable(false)->change();
            $table->index(['class_schedule_id', 'user_id', 'session_date'], 'session_participants_schedule_user_date_index');
            $table->index(['session_date', 'status'], 'session_participants_date_status_index');
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropIndex(['session_date', 'status']);
            $table->dropIndex(['class_id', 'session_date']);
            $table->dropColumn(['session_date', 'status']);
            $table->index(['class_id', 'day'], 'class_schedules_class_day_index');
            $table->index(['day', 'is_active'], 'class_schedules_day_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->date('session_date')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
        });

        DB::table('class_schedules')->update(['session_date' => today()->toDateString()]);

        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropIndex('session_participants_schedule_user_date_index');
            $table->dropIndex('session_participants_date_status_index');
            $table->dropColumn('session_date');
            $table->unique(['class_schedule_id', 'user_id']);
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropIndex('class_schedules_class_day_index');
            $table->dropIndex('class_schedules_day_active_index');
            $table->dropColumn(['day', 'is_active']);
            $table->index(['session_date', 'status']);
            $table->index(['class_id', 'session_date']);
        });
    }
};
