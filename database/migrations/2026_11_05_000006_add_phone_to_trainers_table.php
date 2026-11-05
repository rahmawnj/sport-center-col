<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('trainers', 'phone')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('specialty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trainers', 'phone')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }
    }
};
