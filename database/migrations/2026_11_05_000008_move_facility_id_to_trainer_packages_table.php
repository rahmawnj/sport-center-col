<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_packages', function (Blueprint $table) {
            $table->foreignId('facility_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });

        // Preserve the sport already assigned to each package before removing the pivot.
        if (Schema::hasTable('trainer_package_facility')) {
            $assignments = DB::table('trainer_package_facility')
                ->select('trainer_package_id', 'facility_id')
                ->get();

            foreach ($assignments as $assignment) {
                DB::table('trainer_packages')
                    ->where('id', $assignment->trainer_package_id)
                    ->update(['facility_id' => $assignment->facility_id]);
            }

            Schema::drop('trainer_package_facility');
        }
    }

    public function down(): void
    {
        Schema::create('trainer_package_facility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique('trainer_package_id');
            $table->unique(['trainer_package_id', 'facility_id']);
        });

        DB::table('trainer_packages')
            ->whereNotNull('facility_id')
            ->orderBy('id')
            ->get(['id', 'facility_id'])
            ->each(function ($package) {
                DB::table('trainer_package_facility')->insert([
                    'trainer_package_id' => $package->id,
                    'facility_id' => $package->facility_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('trainer_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_id');
        });
    }
};
