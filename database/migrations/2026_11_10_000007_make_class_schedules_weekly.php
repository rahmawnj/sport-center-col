<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make this migration safe to retry after a partially completed MySQL migration.
        if (! Schema::hasColumn('class_schedules', 'day')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->enum('day', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
                    ->default('monday')
                    ->after('trainer_id');
            });
        }

        if (! Schema::hasColumn('class_schedules', 'is_active')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('price');
            });
        }

        // Convert existing dated schedules into their corresponding weekly day.
        if (Schema::hasColumn('class_schedules', 'session_date')) {
            foreach (DB::table('class_schedules')->select('id', 'session_date', 'status')->get() as $schedule) {
                $day = strtolower(date('l', strtotime($schedule->session_date)));

                DB::table('class_schedules')->where('id', $schedule->id)->update([
                    'day' => $day,
                    'is_active' => $schedule->status === 'scheduled',
                ]);
            }
        }

        if (! Schema::hasColumn('session_participants', 'session_date')) {
            Schema::table('session_participants', function (Blueprint $table) {
                $table->date('session_date')->nullable()->after('class_schedule_id');
            });
        }

        // Backfill participant date before making the new column required.
        if (Schema::hasColumn('class_schedules', 'session_date')) {
            DB::table('session_participants')
                ->join('class_schedules', 'session_participants.class_schedule_id', '=', 'class_schedules.id')
                ->whereNull('session_participants.session_date')
                ->update(['session_participants.session_date' => DB::raw('class_schedules.session_date')]);
        }

        // Replace the old unique index only once. The new composite index starts
        // with class_schedule_id, so it can continue supporting its foreign key.
        if (! Schema::hasIndex('session_participants', 'session_participants_schedule_user_date_index')) {
            Schema::table('session_participants', function (Blueprint $table) {
                $table->dropForeign(['class_schedule_id']);

                if (Schema::hasIndex('session_participants', 'session_participants_class_schedule_id_user_id_unique')) {
                    $table->dropUnique('session_participants_class_schedule_id_user_id_unique');
                }
            });

            Schema::table('session_participants', function (Blueprint $table) {
                $table->date('session_date')->nullable(false)->change();
                $table->index(
                    ['class_schedule_id', 'user_id', 'session_date'],
                    'session_participants_schedule_user_date_index'
                );
            });

            Schema::table('session_participants', function (Blueprint $table) {
                $table->foreign('class_schedule_id')
                    ->references('id')
                    ->on('class_schedules')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasIndex('session_participants', 'session_participants_date_status_index')) {
            Schema::table('session_participants', function (Blueprint $table) {
                $table->index(['session_date', 'status'], 'session_participants_date_status_index');
            });
        }

        // Create the replacement index before dropping the old one. MySQL needs
        // an index whose first column is class_id to keep the class_id FK valid.
        if (! Schema::hasIndex('class_schedules', 'class_schedules_class_day_index')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->index(['class_id', 'day'], 'class_schedules_class_day_index');
            });
        }

        if (Schema::hasColumn('class_schedules', 'session_date')) {
            if (Schema::hasIndex('class_schedules', 'class_schedules_class_id_session_date_index')) {
                Schema::table('class_schedules', function (Blueprint $table) {
                    $table->dropIndex('class_schedules_class_id_session_date_index');
                });
            }

            if (Schema::hasIndex('class_schedules', 'class_schedules_session_date_status_index')) {
                Schema::table('class_schedules', function (Blueprint $table) {
                    $table->dropIndex('class_schedules_session_date_status_index');
                });
            }

            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn(['session_date', 'status']);
            });
        }

        if (! Schema::hasIndex('class_schedules', 'class_schedules_day_active_index')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->index(['day', 'is_active'], 'class_schedules_day_active_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->date('session_date')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
        });

        DB::table('class_schedules')->update(['session_date' => today()->toDateString()]);

        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropForeign(['class_schedule_id']);
            $table->dropIndex('session_participants_schedule_user_date_index');
            $table->dropIndex('session_participants_date_status_index');
            $table->dropColumn('session_date');
            $table->unique(['class_schedule_id', 'user_id']);
            $table->foreign('class_schedule_id')
                ->references('id')
                ->on('class_schedules')
                ->cascadeOnDelete();
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropIndex('class_schedules_class_day_index');
            $table->dropIndex('class_schedules_day_active_index');
            $table->dropColumn(['day', 'is_active']);
            $table->index(['session_date', 'status']);
            $table->index(['class_id', 'session_date']);
        });
    }
};
