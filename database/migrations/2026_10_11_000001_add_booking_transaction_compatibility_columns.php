<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the current transaction schema while supporting the booking/payment
        // flow already used by the application (including morph relationships).
        DB::statement("ALTER TABLE transactions
            MODIFY booking_code VARCHAR(255) NULL,
            MODIFY payment_method VARCHAR(255) NULL,
            MODIFY total_amount DECIMAL(15,2) NULL,
            MODIFY payment_status VARCHAR(255) NOT NULL DEFAULT 'pending'");

        Schema::table('transactions', function ($table) {
            $table->string('customer_name')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('qr_code')->nullable()->unique();
            $table->string('transactionable_type')->nullable()->index();
            $table->unsignedBigInteger('transactionable_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function ($table) {
            $table->dropUnique(['invoice_number']);
            $table->dropUnique(['qr_code']);
            $table->dropIndex(['transactionable_type']);
            $table->dropIndex(['transactionable_id']);
            $table->dropColumn([
                'customer_name',
                'amount',
                'invoice_number',
                'qr_code',
                'transactionable_type',
                'transactionable_id',
            ]);
        });

        // Deliberately do not narrow payment status or make columns non-nullable
        // during rollback, because existing rows may already use the new values.
    }
};
