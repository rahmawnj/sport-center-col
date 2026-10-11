<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasId = Schema::hasColumn('transactions', 'transactionable_id');
        $hasType = Schema::hasColumn('transactions', 'transactionable_type');

        if ($hasId && $hasType) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) use ($hasId, $hasType) {
            if (! $hasId && ! $hasType) {
                $table->nullableMorphs('transactionable');
                return;
            }

            if (! $hasId) {
                $table->unsignedBigInteger('transactionable_id')->nullable();
            }

            if (! $hasType) {
                $table->string('transactionable_type')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Keep the relationship columns intact; an earlier migration may own them.
    }
};
