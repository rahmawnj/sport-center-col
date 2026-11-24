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
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            $table->enum('payment_review_status', ['pending', 'accepted', 'rejected'])
                ->default('pending')
                ->after('payment_status');
        });

        // Existing unpaid and partial-payment transactions require verification.
        // Only fully paid legacy transactions remain accepted.
        DB::table('transactions')
            ->whereIn('payment_status', ['unpaid', 'dp_paid'])
            ->update(['payment_review_status' => 'pending']);

        DB::table('transactions')
            ->where('payment_status', 'fully_paid')
            ->update(['paid_amount' => DB::raw('total_amount'), 'payment_review_status' => 'accepted']);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'payment_review_status']);
        });
    }
};
