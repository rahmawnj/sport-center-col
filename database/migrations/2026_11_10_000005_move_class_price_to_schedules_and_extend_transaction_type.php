<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasClassPrice = Schema::hasColumn('classes', 'price');
        $hasSchedulePrice = Schema::hasColumn('class_schedules', 'price');

        if (! $hasSchedulePrice) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->decimal('price', 12, 2)->default(0)->after('capacity');
            });
        }

        if ($hasClassPrice) {
            DB::table('class_schedules')
                ->join('classes', 'classes.id', '=', 'class_schedules.class_id')
                ->update(['class_schedules.price' => DB::raw('classes.price')]);

            Schema::table('classes', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transactionable_type', ['membership', 'booking', 'class'])
                ->default('booking')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transactionable_type', ['membership', 'booking'])
                ->default('booking')
                ->change();
        });

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
