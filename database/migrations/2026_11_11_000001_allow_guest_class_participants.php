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
        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['guest_name', 'guest_email', 'guest_phone']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('session_participants', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
