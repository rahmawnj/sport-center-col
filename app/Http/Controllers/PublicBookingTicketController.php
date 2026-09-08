<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingTicketController extends Controller
{
    public function show(Transaction $transaction): Response
    {
        $transaction->load([
            'details.zoneSpace.zone',
            'paymentProofs',
        ]);

        $detail = $transaction->details->first();
        $proof = $transaction->paymentProofs->first();

        return Inertia::render('booking/Ticket', [
            'booking' => [
                'id' => $transaction->id,
                'booking_code' => $transaction->booking_code,
                'guest_name' => $transaction->guest_name,
                'booking_status' => $transaction->booking_status,
                'payment_status' => $transaction->payment_status,
                'payment_method' => $transaction->payment_method,
                'total_amount' => (float) $transaction->total_amount,
                'created_at' => $transaction->created_at?->toISOString(),
                'zone' => $detail?->zoneSpace?->zone?->name,
                'space' => $detail?->zoneSpace?->name,
                'start_time' => $detail?->start_time?->toISOString(),
                'end_time' => $detail?->end_time?->toISOString(),
                'payment_option' => $proof?->payment_option,
                'amount_paid' => $proof ? (float) $proof->amount_paid : 0,
            ],
        ]);
    }
}
