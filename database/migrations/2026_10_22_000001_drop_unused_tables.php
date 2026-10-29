<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove tables that are not used by any routed feature.
     * The live schema relies on the legacy facility/booking catalog plus the
     * transactions, trainers, and membership_packages tables.
     *
     * @var array<int, string>
     */
    private array $tables = [
        // Spatie permission tables (roles/users.role_id is the real mechanism).
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'permissions',

        // Unused zone/space/pricing system.
        'facility_zone_space',
        'pricing_rates',
        'operational_hours',
        'zone_spaces',
        'zones',
        'add_ons',

        // Unused transaction breakdown/payment tables.
        'transaction_add_ons',
        'transaction_details',
        'transaction_payment_proofs',

        // Unused finance tables.
        'cash_transactions',
        'daily_closings',

        // Unused IoT/gate and subscription tables.
        'iot_logs',
        'iot_devices',
        'gate_access_logs',
        'user_subscriptions',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Destructive cleanup: the dropped tables are intentionally not recreated.
    }
};
