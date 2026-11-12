<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transactionable_type', ['membership', 'booking', 'trainer_package', 'class', 'entry'])
                ->default('booking')
                ->change();
        });
    }

    public function down(): void
    {
        // The previous enum cannot represent admission-ticket transactions.
        // Preserve those rows as generic class/session transactions on rollback.
        DB::table('transactions')
            ->where('transactionable_type', 'entry')
            ->update(['transactionable_type' => 'class']);

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transactionable_type', ['membership', 'booking', 'trainer_package', 'class'])
                ->default('booking')
                ->change();
        });
    }
};
