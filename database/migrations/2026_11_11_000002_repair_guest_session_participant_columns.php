<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('session_participants')) {
            return;
        }

        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('session_participants', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('session_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('session_participants', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('session_participants', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('guest_name');
            }

            if (! Schema::hasColumn('session_participants', 'guest_phone')) {
                $table->string('guest_phone', 50)->nullable()->after('guest_email');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('session_participants')) {
            return;
        }

        // The previous schema requires user_id. Guest participants cannot be
        // represented after rollback, so remove only those rows before restoring
        // the NOT NULL constraint. This path is normally used by migrate:refresh.
        DB::table('session_participants')->whereNull('user_id')->delete();

        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        $columns = array_values(array_filter(
            ['guest_name', 'guest_email', 'guest_phone'],
            fn (string $column) => Schema::hasColumn('session_participants', $column)
        ));

        if ($columns !== []) {
            Schema::table('session_participants', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
