<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The booking flow needs a bookings table, but some installations of
        // this project do not have the legacy table yet. Create its base
        // structure first so this migration works on a fresh database too.
        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('court_id')->nullable()->constrained('courts')->nullOnDelete();
                $table->string('guest_name');
                $table->string('guest_email');
                $table->string('guest_phone', 50);
                $table->date('date');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('status')->default('pending');
                $table->timestamps();

                $table->index(['date', 'status']);
                $table->index(['court_id', 'date']);
            });
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'zone_space_id')) {
                $table->foreignId('zone_space_id')->nullable()->after('court_id')->constrained('zone_spaces')->nullOnDelete();
            }

            if (! Schema::hasColumn('bookings', 'pricing_rate_id')) {
                $table->foreignId('pricing_rate_id')->nullable()->after('zone_space_id')->constrained('pricing_rates')->nullOnDelete();
            }

            $table->foreignId('court_id')->nullable()->change();
        });

        if (! Schema::hasTable('booking_add_ons')) {
            Schema::create('booking_add_ons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->foreignId('add_on_id')->constrained('add_ons')->restrictOnDelete();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 12, 2);
                $table->timestamps();
                $table->unique(['booking_id', 'add_on_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_add_ons');

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'pricing_rate_id')) {
                    $table->dropConstrainedForeignId('pricing_rate_id');
                }

                if (Schema::hasColumn('bookings', 'zone_space_id')) {
                    $table->dropConstrainedForeignId('zone_space_id');
                }
            });
        }
    }
};
