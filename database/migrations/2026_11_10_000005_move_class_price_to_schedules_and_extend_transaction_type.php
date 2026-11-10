<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->after('capacity');
        });

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

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0);
        });
    }
};
