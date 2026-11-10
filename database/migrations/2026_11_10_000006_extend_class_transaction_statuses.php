<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transactionable_type', ['membership', 'booking', 'trainer_package', 'class'])
                ->default('booking')
                ->change();

            $table->enum('payment_status', ['unpaid', 'dp_paid', 'fully_paid', 'failed', 'refunded'])
                ->default('unpaid')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('transactions')
            ->where('transactionable_type', 'class')
            ->update(['transactionable_type' => 'booking']);

        DB::table('transactions')
            ->where('payment_status', 'failed')
            ->update(['payment_status' => 'unpaid']);

        DB::table('transactions')
            ->where('payment_status', 'refunded')
            ->update(['payment_status' => 'fully_paid']);

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transactionable_type', ['membership', 'booking', 'trainer_package'])
                ->default('booking')
                ->change();

            $table->enum('payment_status', ['unpaid', 'dp_paid', 'fully_paid'])
                ->default('unpaid')
                ->change();
        });
    }
};
