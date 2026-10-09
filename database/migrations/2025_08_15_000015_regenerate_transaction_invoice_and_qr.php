<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transactions')->orderBy('id')->get()->each(function ($row) {
            $invoice = 'INV'.date('YmdHis', strtotime((string) $row->created_at)).strtoupper(Str::random(4));
            $digits = implode('', array_map(fn () => random_int(0, 9), range(1, 16)));

            DB::table('transactions')->where('id', $row->id)->update([
                'invoice_number' => $invoice,
                'qr_code' => strtoupper(Str::random(8)).$digits,
            ]);
        });
    }

    public function down(): void
    {
        // Data-only migration; nothing to reverse.
    }
};
