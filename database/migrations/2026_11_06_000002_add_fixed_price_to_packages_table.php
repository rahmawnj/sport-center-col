<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->after('duration_days');
        });

        DB::statement("
            UPDATE packages
            SET price = COALESCE((
                SELECT MIN(package_pricing_rules.price)
                FROM package_pricing_rules
                WHERE package_pricing_rules.package_id = packages.id
            ), 0)
            WHERE type = 'membership'
        ");

        DB::statement("
            DELETE package_pricing_rules
            FROM package_pricing_rules
            INNER JOIN packages ON packages.id = package_pricing_rules.package_id
            WHERE packages.type = 'membership'
        ");
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
