<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_trainer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['facility_id', 'trainer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_trainer');
    }
};
