<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_session_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_trainer_package_id')->constrained('member_trainer_packages')->cascadeOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('scheduled');
            $table->boolean('member_attended')->default(false);
            $table->boolean('trainer_attended')->default(false);
            $table->timestamp('member_confirmed_at')->nullable();
            $table->timestamp('trainer_confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['session_date', 'start_time', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_session_schedules');
    }
};
