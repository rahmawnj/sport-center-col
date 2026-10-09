<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->comment('NULL jika tamu/guest yang booking')->constrained()->nullOnDelete();
            $table->string('guest_name')->nullable()->comment('Wajib diisi jika user_id NULL');
            $table->string('guest_email')->nullable()->comment('Wajib diisi jika user_id NULL');
            $table->string('guest_phone', 50)->nullable()->comment('Wajib diisi jika user_id NULL');
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
