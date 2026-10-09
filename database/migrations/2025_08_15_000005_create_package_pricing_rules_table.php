<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->enum('day_type', ['weekday', 'weekend', 'all'])->default('all');
            $table->time('start_time')->nullable()->comment('NULL jika berlaku seharian');
            $table->time('end_time')->nullable()->comment('NULL jika berlaku seharian');
            $table->decimal('price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_pricing_rules');
    }
};
