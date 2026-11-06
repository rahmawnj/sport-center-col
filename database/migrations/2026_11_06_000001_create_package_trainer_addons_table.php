<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_trainer_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['package_id', 'trainer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_trainer_addons');
    }
};
