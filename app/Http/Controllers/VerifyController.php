<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerifyController extends Controller
{
    public function show(Request $request): Response
    {
        $code = (string) $request->query('code', '');

        $transaction = $code !== ''
            ? Transaction::query()->where('qr_code', $code)->first()
            : null;

        return Inertia::render('verify/Show', [
            'code' => $code,
            'found' => (bool) $transaction,
            'transaction' => $transaction ? [
                'invoice_number' => $transaction->invoice_number,
                'customer_name' => $transaction->customer_name,
                'amount' => (float) $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'payment_status' => $transaction->payment_status,
                'created_at' => $transaction->created_at?->toISOString(),
            ] : null,
        ]);
    }
}
