<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('scan_count')->default(0)->after('status');
        });

        Schema::table('user_memberships', function (Blueprint $table) {
            $table->unsignedInteger('scan_count')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('scan_count');
        });

        Schema::table('user_memberships', function (Blueprint $table) {
            $table->dropColumn('scan_count');
        });
    }
};
