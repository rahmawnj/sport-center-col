<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_pricing_rules', function (Blueprint $table) {
            $table->date('date_start')->nullable()->after('day_type');
            $table->date('date_end')->nullable()->after('date_start');
            $table->integer('priority')->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('package_pricing_rules', function (Blueprint $table) {
            $table->dropColumn(['date_start', 'date_end', 'priority']);
        });
    }
};
