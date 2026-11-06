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
            $table->string('invoice_number')->unique();
            $table->enum('transactionable_type', ['membership', 'booking'])->default('booking');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer']);
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_status', ['unpaid', 'dp_paid', 'fully_paid'])->default('unpaid');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('zone_space_id')->constrained('zone_spaces')->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('trainers')->nullOnDelete();
            $table->integer('qty')->default(1);
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('price_rate', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('transaction_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained('add_ons')->cascadeOnDelete();
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
