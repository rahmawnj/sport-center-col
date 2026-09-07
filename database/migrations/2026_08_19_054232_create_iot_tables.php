<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_space_id')->constrained('zone_spaces')->onDelete('cascade');
            $table->string('device_code')->unique();
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->timestamps();
        });

        Schema::create('iot_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->constrained('iot_devices')->onDelete('cascade');
            $table->enum('action', ['turn_on', 'turn_off']);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_logs');
        Schema::dropIfExists('iot_devices');
    }
};
