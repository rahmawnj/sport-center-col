<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_trainer_packages', function (Blueprint $table) {
            $table->foreignId('facility_id')->after('trainer_id')->constrained()->restrictOnDelete();
            $table->index(['facility_id', 'status']);
        });

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

        Schema::table('member_trainer_packages', function (Blueprint $table) {
            $table->dropIndex(['facility_id', 'status']);
            $table->dropConstrainedForeignId('facility_id');
        });
    }
};
