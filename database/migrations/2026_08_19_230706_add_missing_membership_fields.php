<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add session_quota to membership_packages when the legacy table exists.
        if (Schema::hasTable('membership_packages') && ! Schema::hasColumn('membership_packages', 'session_quota')) {
            Schema::table('membership_packages', function (Blueprint $table) {
                $table->integer('session_quota')->nullable()->after('duration_days')
                    ->comment('Batas kedatangan/sesi. Isi NULL jika Unlimited');
            });
        }

        // This legacy table may be removed by the later cleanup migration.
        if (Schema::hasTable('user_subscriptions') && ! Schema::hasColumn('user_subscriptions', 'used_sessions')) {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                $table->integer('used_sessions')->default(0)->after('end_date')
                    ->comment('Jumlah sesi yang sudah dipakai');
            });
        }
    }

    public function down(): void
    {
        // The cleanup migration intentionally drops user_subscriptions.
        // During migrate:refresh this rollback must tolerate that table being absent.
        if (Schema::hasTable('user_subscriptions') && Schema::hasColumn('user_subscriptions', 'used_sessions')) {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                $table->dropColumn('used_sessions');
            });
        }

        if (Schema::hasTable('membership_packages') && Schema::hasColumn('membership_packages', 'session_quota')) {
            Schema::table('membership_packages', function (Blueprint $table) {
                $table->dropColumn('session_quota');
            });
        }
    }
};
