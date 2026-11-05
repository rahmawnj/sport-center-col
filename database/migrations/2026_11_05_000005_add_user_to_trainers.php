<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained('users')->nullOnDelete();
        });

        DB::table('roles')->updateOrInsert(
            ['name' => 'Trainer'],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        DB::table('roles')->where('name', 'Trainer')->delete();
    }
};
