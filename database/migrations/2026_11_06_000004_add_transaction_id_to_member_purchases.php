<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_memberships', function (Blueprint $table) {
            $table->foreignId('transaction_id')->nullable()->after('user_id')->constrained('transactions')->nullOnDelete();
        });

        Schema::table('member_trainer_packages', function (Blueprint $table) {
            $table->foreignId('transaction_id')->nullable()->after('user_id')->constrained('transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('member_trainer_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaction_id');
        });

        Schema::table('user_memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaction_id');
        });
    }
};
