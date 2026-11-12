<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_packages', function (Blueprint $table) {
            $table->decimal('trainer_share_percentage', 5, 2)->default(0)->after('price');
        });

        DB::table('member_trainer_packages')
            ->join('trainer_packages', 'trainer_packages.id', '=', 'member_trainer_packages.trainer_package_id')
            ->update([
                'member_trainer_packages.trainer_share_percentage' => DB::raw('trainer_packages.trainer_share_percentage'),
            ]);
    }

    public function down(): void
    {
        Schema::table('trainer_packages', function (Blueprint $table) {
            $table->dropColumn('trainer_share_percentage');
        });
    }
};
