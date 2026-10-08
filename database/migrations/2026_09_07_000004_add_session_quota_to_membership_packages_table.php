<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The session_quota column is already added by the earlier
     * 2026_08_19_230706_add_missing_membership_fields migration.
     *
     * This migration is intentionally kept as a no-op to preserve migration
     * history without attempting to add the column a second time.
     */
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
