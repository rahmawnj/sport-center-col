<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('zone_space_id')->nullable()->after('court_id')->constrained('zone_spaces')->nullOnDelete();
            $table->foreignId('pricing_rate_id')->nullable()->after('zone_space_id')->constrained('pricing_rates')->nullOnDelete();
            $table->foreignId('court_id')->nullable()->change();
        });

        Schema::create('booking_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained('add_ons')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
            $table->unique(['booking_id', 'add_on_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_add_ons');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pricing_rate_id');
            $table->dropConstrainedForeignId('zone_space_id');
        });
    }
};
