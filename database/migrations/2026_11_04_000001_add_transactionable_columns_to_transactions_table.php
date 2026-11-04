<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('transactionable_type')->nullable()->after('booking_code');
            $table->unsignedBigInteger('transactionable_id')->nullable()->after('transactionable_type');
            $table->index(['transactionable_type', 'transactionable_id'], 'transactions_transactionable_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_transactionable_index');
            $table->dropColumn(['transactionable_type', 'transactionable_id']);
        });
    }
};
