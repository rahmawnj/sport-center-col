<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('has_flexible_booking')->default(false)->after('has_booking');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('booking_type', ['fixed', 'flexible'])->default('fixed')->after('end_time');
            $table->json('price_breakdown')->nullable()->after('booking_type');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_type', 'price_breakdown']);
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('has_flexible_booking');
        });
    }
};
