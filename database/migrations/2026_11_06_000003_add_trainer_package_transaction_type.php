<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY transactionable_type ENUM('membership', 'booking', 'trainer_package') NOT NULL DEFAULT 'booking'");
    }

    public function down(): void
    {
        DB::table('transactions')
            ->where('transactionable_type', 'trainer_package')
            ->update(['transactionable_type' => 'membership']);

        DB::statement("ALTER TABLE transactions MODIFY transactionable_type ENUM('membership', 'booking') NOT NULL DEFAULT 'booking'");
    }
};
