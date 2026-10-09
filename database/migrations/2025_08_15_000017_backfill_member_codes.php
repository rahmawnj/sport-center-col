<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('member_profiles')
            ->whereNull('member_code')
            ->orWhereNull('qr_code')
            ->orderBy('id')
            ->get()
            ->each(function ($row) {
                $update = [];

                if (empty($row->member_code)) {
                    $digits = str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
                    $update['member_code'] = 'MEM'.strtoupper(Str::random(4)).$digits;
                }

                if (empty($row->qr_code)) {
                    $digits = implode('', array_map(fn () => random_int(0, 9), range(1, 16)));
                    $update['qr_code'] = strtoupper(Str::random(8)).$digits;
                }

                if ($update !== []) {
                    DB::table('member_profiles')->where('id', $row->id)->update($update);
                }
            });
    }

    public function down(): void
    {
        // Data-only migration; nothing to reverse.
    }
};
