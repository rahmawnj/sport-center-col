<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_phone', 50)->nullable()->after('guest_email');
        });

        Schema::table('session_participants', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('session_participants')) {
            return;
        }

        // Migration 2026_11_11_000002 may already have removed these columns
        // during migrate:refresh. Drop only columns that still exist.
        $columns = array_values(array_filter(
            ['guest_name', 'guest_email', 'guest_phone'],
            fn (string $column) => Schema::hasColumn('session_participants', $column)
        ));

        Schema::table('session_participants', function (Blueprint $table) use ($columns) {
            $table->dropForeign(['user_id']);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }

            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('session_participants', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
