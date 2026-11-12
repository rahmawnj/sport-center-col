<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_trainer_packages', function (Blueprint $table) {
            $table->decimal('trainer_share_percentage', 5, 2)->default(0)->after('used_sessions');
        });
    }

    public function down(): void
    {
        Schema::table('member_trainer_packages', function (Blueprint $table) {
            $table->dropColumn('trainer_share_percentage');
        });
    }
};
