<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY payment_status ENUM('unpaid', 'dp_paid', 'fully_paid', 'failed', 'refunded') NOT NULL DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        DB::table('transactions')
            ->whereIn('payment_status', ['failed', 'refunded'])
            ->update(['payment_status' => 'unpaid']);

        DB::statement("ALTER TABLE transactions MODIFY payment_status ENUM('unpaid', 'dp_paid', 'fully_paid') NOT NULL DEFAULT 'unpaid'");
    }
};
