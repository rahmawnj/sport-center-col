<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->unique()->after('id');
            $table->string('qr_code', 500)->nullable()->after('invoice_number');
        });

        DB::table('transactions')->whereNull('invoice_number')->orderBy('id')->get()->each(function ($row) {
            $invoice = 'INV/'.date('YmdHis', strtotime((string) $row->created_at)).strtoupper(Str::random(4));

            DB::table('transactions')->where('id', $row->id)->update([
                'invoice_number' => $invoice,
                'qr_code' => rtrim((string) config('app.url'), '/').'/verify?inv='.rawurlencode($invoice),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'qr_code']);
        });
    }
};
