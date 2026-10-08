<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The deleted_at column is already added by the earlier
     * 2026_08_19_064814_add_role_relationship_to_users_table migration.
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
