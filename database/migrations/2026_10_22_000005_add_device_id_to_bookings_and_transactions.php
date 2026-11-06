<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('device_id')
                ->nullable()
                ->after('court_id')
                ->constrained('devices')
                ->nullOnDelete();
        });

        Schema::table('user_memberships', function (Blueprint $table) {
            $table->foreignId('device_id')
                ->nullable()
                ->after('package_id')
                ->constrained('devices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_memberships', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropColumn('device_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropColumn('device_id');
        });
    }
};
