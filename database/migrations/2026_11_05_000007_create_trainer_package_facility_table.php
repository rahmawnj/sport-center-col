<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_package_facility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique('trainer_package_id');
            $table->unique(['trainer_package_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_package_facility');
    }
};
