<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['cash_in', 'cash_out', 'petty_cash_update', 'transfer_to_bank']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->date('closing_date');
            $table->decimal('total_system_cash', 15, 2);
            $table->decimal('total_actual_cash', 15, 2);
            $table->decimal('total_bank_transfer', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
        Schema::dropIfExists('cash_transactions');
    }
};
