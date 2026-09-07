<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add missing fields to pricing_rates table
        Schema::table('pricing_rates', function (Blueprint $table) {
            $table->foreignId('trainer_id')->nullable()->constrained('trainers')->onDelete('cascade')->after('zone_space_id');
            $table->string('day_of_week', 50)->nullable()->after('min_booking_duration')
                ->comment('Format CSV: 1,2,3,4,5 untuk Weekday. NULL = Tiap hari');
            $table->time('start_time')->nullable()->after('day_of_week')
                ->comment('Jam awal promo berlaku');
            $table->time('end_time')->nullable()->after('start_time')
                ->comment('Jam akhir promo berlaku');
            $table->boolean('is_active')->default(true)->after('end_time');
        });

        // 2. Fix phone field length in users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->change();
        });

        // 3. Remove phone field from trainers (not in original SQL)
        Schema::table('trainers', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        // 4. Create missing gate_access_logs table
        Schema::create('gate_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->enum('action', ['check_in', 'check_out']);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        // Drop gate_access_logs table
        Schema::dropIfExists('gate_access_logs');

        // Revert trainers phone field (add back)
        Schema::table('trainers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('specialty');
        });

        // Revert users phone length
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });

        // Remove pricing_rates enhancements
        Schema::table('pricing_rates', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropColumn(['trainer_id', 'day_of_week', 'start_time', 'end_time', 'is_active']);
        });
    }
};
