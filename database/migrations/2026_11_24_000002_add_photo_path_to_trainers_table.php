<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trainers')) {
            return;
        }

        if (! Schema::hasColumn('trainers', 'photo_path')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->string('photo_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('trainers') && Schema::hasColumn('trainers', 'photo_path')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->dropColumn('photo_path');
            });
        }
    }
};
