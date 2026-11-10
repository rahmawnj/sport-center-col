<?php

use Illuminate\Database\\Migrations\\Migration;
use Illuminate\Database\\Schema\\Blueprint;
use Illuminate\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('class_schedules', 'price')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->decimal('price', 12, 2)->default(0)->after('capacity');
            });
        }

        if (Schema::hasColumn('classes', 'price')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('classes', 'price')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->decimal('price', 12, 2)->default(0)->after('default_capacity');
            });
        }

        if (Schema::hasColumn('class_schedules', 'price')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
