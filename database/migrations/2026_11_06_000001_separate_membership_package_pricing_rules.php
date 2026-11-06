<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_package_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('day_type', 20)->default('all');
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('priority')->default(0);
            $table->timestamps();
            $table->index(['package_id', 'priority']);
        });

        if (Schema::hasTable('package_pricing_rules')) {
            DB::table('membership_package_pricing_rules')->insertUsing(
                ['package_id', 'day_type', 'date_start', 'date_end', 'start_time', 'end_time', 'price', 'priority', 'created_at', 'updated_at'],
                DB::table('package_pricing_rules')
                    ->join('packages', 'packages.id', '=', 'package_pricing_rules.package_id')
                    ->where('packages.type', 'membership')
                    ->select([
                        'package_pricing_rules.package_id',
                        'package_pricing_rules.day_type',
                        'package_pricing_rules.date_start',
                        'package_pricing_rules.date_end',
                        'package_pricing_rules.start_time',
                        'package_pricing_rules.end_time',
                        'package_pricing_rules.price',
                        'package_pricing_rules.priority',
                        'package_pricing_rules.created_at',
                        'package_pricing_rules.updated_at',
                    ])
            );

            DB::table('package_pricing_rules')
                ->whereIn('package_id', DB::table('packages')->select('id')->where('type', 'membership'))
                ->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('package_pricing_rules')) {
            DB::table('package_pricing_rules')->insertUsing(
                ['package_id', 'day_type', 'date_start', 'date_end', 'start_time', 'end_time', 'price', 'priority', 'created_at', 'updated_at'],
                DB::table('membership_package_pricing_rules')->select([
                    'package_id', 'day_type', 'date_start', 'date_end', 'start_time', 'end_time', 'price', 'priority', 'created_at', 'updated_at',
                ])
            );
        }

        Schema::dropIfExists('membership_package_pricing_rules');
    }
};
