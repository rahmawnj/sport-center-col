<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Zones (Zona/Fasilitas Utama)
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('pricing_model', ['per_person', 'per_space', 'per_trainer_session', 'per_table']);
            $table->boolean('is_online_bookable')->default(false);
            $table->timestamps();
        });

        // Operational Hours (Jam Operasional per Zona)
        Schema::create('operational_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->tinyInteger('day_of_week')->comment('1=Senin, 2=Selasa, 3=Rabu, 4=Kamis, 5=Jumat, 6=Sabtu, 7=Minggu');
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });

        // Zone Spaces (Ruang/Space dalam zona)
        Schema::create('zone_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->string('name');
            $table->integer('capacity')->default(1);
            $table->enum('status', ['available', 'maintenance'])->default('available');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones_system_tables');
    }
};
