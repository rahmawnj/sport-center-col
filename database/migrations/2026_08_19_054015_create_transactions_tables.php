<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->enum('customer_type', ['member', 'general'])->default('general');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('guest_name')->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer']);
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_status', ['unpaid', 'dp_paid', 'fully_paid'])->default('unpaid');
            $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('zone_space_id')->constrained('zone_spaces')->onDelete('cascade');
            $table->foreignId('trainer_id')->nullable()->constrained('trainers')->onDelete('set null');
            $table->integer('qty')->default(1);
            $table->datetime('start_time');
            $table->datetime('end_time');
            $table->decimal('price_rate', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('transaction_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('add_on_id')->constrained('add_ons')->onDelete('cascade');
            $table->integer('qty')->default(1);
            $table->decimal('price_rate', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_add_ons');
        Schema::dropIfExists('transaction_details');
        Schema::dropIfExists('transactions');
    }
};
