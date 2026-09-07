<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_space_id')->constrained('zone_spaces')->onDelete('cascade');
            $table->string('rental_type');
            $table->decimal('price', 15, 2);
            $table->enum('unit_type', ['per_hour', 'per_visit', 'per_session']);
            $table->integer('min_booking_duration')->default(1);
            $table->timestamps();
        });

        Schema::create('add_ons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->integer('stock')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_ons');
        Schema::dropIfExists('pricing_rates');
    }
};
