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
            $table->enum('booking_source', ['website', 'cashier'])->default('website')->after('transactionable_type');
            $table->foreignId('input_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->boolean('input_by_automatic')->default(true)->after('input_by');
            $table->boolean('handled_by_automatic')->default(true)->after('handled_by');
        });

        DB::table('transactions')->whereNotNull('handled_by')->update([
            'handled_by_automatic' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['input_by']);
            $table->dropColumn(['booking_source', 'input_by', 'input_by_automatic', 'handled_by_automatic']);
        });
    }
};
