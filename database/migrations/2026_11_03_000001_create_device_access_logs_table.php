<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('access_type', 20);
            $table->string('source', 20);
            $table->string('identifier', 255)->nullable();
            $table->boolean('allowed')->default(false);
            $table->string('reason')->nullable();
            $table->decimal('similarity', 8, 5)->nullable();
            $table->timestamps();

            $table->index(['device_id', 'created_at']);
            $table->index(['access_type', 'allowed', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_access_logs');
    }
};
