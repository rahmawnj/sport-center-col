<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add pricing fields only when the base table exists and the fields are missing.
        if (Schema::hasTable('pricing_rates')) {
            if (!Schema::hasColumn('pricing_rates', 'trainer_id')) {
                Schema::table('pricing_rates', function (Blueprint $table) {
                    $table->foreignId('trainer_id')->nullable()->constrained('trainers')->cascadeOnDelete()->after('zone_space_id');
                });
            }

            if (!Schema::hasColumn('pricing_rates', 'day_of_week')) {
                Schema::table('pricing_rates', function (Blueprint $table) {
                    $table->string('day_of_week', 50)->nullable()->after('min_booking_duration')
                        ->comment('Format CSV: 1,2,3,4,5 untuk Weekday. NULL = Tiap hari');
                });
            }

            if (!Schema::hasColumn('pricing_rates', 'start_time')) {
                Schema::table('pricing_rates', function (Blueprint $table) {
                    $table->time('start_time')->nullable()->after('day_of_week')
                        ->comment('Jam awal promo berlaku');
                });
            }

            if (!Schema::hasColumn('pricing_rates', 'end_time')) {
                Schema::table('pricing_rates', function (Blueprint $table) {
                    $table->time('end_time')->nullable()->after('start_time')
                        ->comment('Jam akhir promo berlaku');
                });
            }

            if (!Schema::hasColumn('pricing_rates', 'is_active')) {
                Schema::table('pricing_rates', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('end_time');
                });
            }
        }

        // Fix phone field length in users table when the column exists.
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 50)->nullable()->change();
            });
        }

        // Remove trainers.phone only when it exists.
        if (Schema::hasTable('trainers') && Schema::hasColumn('trainers', 'phone')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }

        // Create gate logs only once.
        if (!Schema::hasTable('gate_access_logs') && Schema::hasTable('users') && Schema::hasTable('zones')) {
            Schema::create('gate_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
                $table->enum('action', ['check_in', 'check_out']);
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_access_logs');

        // Restore trainers.phone only if the trainers table exists and the column is absent.
        if (Schema::hasTable('trainers') && !Schema::hasColumn('trainers', 'phone')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('specialty');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->change();
            });
        }

        // A refresh/rollback must also work when an earlier migration or manual cleanup
        // has already removed pricing_rates.
        if (Schema::hasTable('pricing_rates')) {
            $foreignKeys = Schema::getForeignKeys('pricing_rates');

            foreach ($foreignKeys as $foreignKey) {
                if (in_array('trainer_id', $foreignKey['columns'] ?? [], true)) {
                    Schema::table('pricing_rates', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                    break;
                }
            }

            $columnsToDrop = array_values(array_filter(
                ['trainer_id', 'day_of_week', 'start_time', 'end_time', 'is_active'],
                fn (string $column): bool => Schema::hasColumn('pricing_rates', $column)
            ));

            if ($columnsToDrop !== []) {
                Schema::table('pricing_rates', function (Blueprint $table) use ($columnsToDrop) {
                    $table->dropColumn($columnsToDrop);
                });
            }
        }
    }
};
