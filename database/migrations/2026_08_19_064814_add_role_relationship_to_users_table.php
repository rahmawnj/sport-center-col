<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add role_id for relationship to roles table
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            
            // Add other fields from sport center SQL
            $table->string('phone')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['phone', 'deleted_at']);
        });
    }
};
