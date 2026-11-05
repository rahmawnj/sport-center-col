<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('trainer_packages', 'facility_id')) {
            Schema::table('trainer_packages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('facility_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('trainer_packages', 'facility_id')) {
            Schema::table('trainer_packages', function (Blueprint $table) {
                $table->foreignId('facility_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }
    }
};
