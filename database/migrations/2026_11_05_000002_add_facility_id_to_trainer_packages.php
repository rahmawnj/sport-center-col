<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_packages', function (Blueprint $table) {
            $table->foreignId('facility_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainer_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_id');
        });
    }
};
