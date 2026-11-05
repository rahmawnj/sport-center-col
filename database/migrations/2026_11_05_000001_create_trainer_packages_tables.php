<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('total_sessions');
            $table->unsignedInteger('duration_days');
            $table->decimal('price', 15, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('member_trainer_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('trainer_package_id')->constrained()->restrictOnDelete();
            $table->foreignId('trainer_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('expires_at');
            $table->unsignedInteger('total_sessions');
            $table->unsignedInteger('used_sessions')->default(0);
            $table->enum('status', ['pending', 'active', 'completed', 'expired', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['trainer_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_trainer_packages');
        Schema::dropIfExists('trainer_packages');
    }
};
