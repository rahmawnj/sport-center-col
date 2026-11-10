<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->enum('day_type', ['all', 'weekday', 'weekend'])->default('all');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['facility_id', 'day_type', 'is_active', 'start_time'], 'booking_slots_facility_day_active_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_time_slots');
    }
};
