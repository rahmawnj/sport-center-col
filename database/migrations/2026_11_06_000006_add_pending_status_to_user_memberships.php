<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE user_memberships MODIFY status ENUM('pending', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'active'");

        // Repair records created by checkout before pending membership status existed.
        DB::statement("
            UPDATE user_memberships
            INNER JOIN transactions ON transactions.id = user_memberships.transaction_id
            SET user_memberships.status = CASE
                WHEN transactions.payment_status IN ('failed', 'refunded') THEN 'cancelled'
                ELSE 'pending'
            END
            WHERE transactions.payment_status IN ('unpaid', 'dp_paid', 'failed', 'refunded')
              AND user_memberships.status = 'active'
        ");
    }

    public function down(): void
    {
        DB::table('user_memberships')
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        DB::statement("ALTER TABLE user_memberships MODIFY status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active'");
    }
};
