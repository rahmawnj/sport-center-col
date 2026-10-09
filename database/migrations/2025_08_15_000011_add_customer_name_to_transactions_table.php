<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Legacy transactions schema superseded by
     * 2026_08_19_054015_create_transactions_tables.php.
     */
    public function up(): void
    {
        // Intentionally left empty to prevent conflicting transactions schemas.
    }

    public function down(): void
    {
        // The current transactions schema is managed by the 2026 migration.
    }
};
