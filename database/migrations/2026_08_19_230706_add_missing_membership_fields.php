<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Fields are now defined in the base membership system migration.
     * Keep this historical migration as a no-op for existing databases.
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
