<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add session_quota to membership_packages
        Schema::table('membership_packages', function (Blueprint $table) {
            $table->integer('session_quota')->nullable()->after('duration_days')
                ->comment('Batas kedatangan/sesi. Isi NULL jika Unlimited');
        });
        
        // Add used_sessions to user_subscriptions  
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->integer('used_sessions')->default(0)->after('end_date')
                ->comment('Jumlah sesi yang sudah dipakai');
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn('used_sessions');
        });
        
        Schema::table('membership_packages', function (Blueprint $table) {
            $table->dropColumn('session_quota');
        });
    }
};
