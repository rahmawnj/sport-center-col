<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class IoTBookingController extends Controller
{
    public function index(): JsonResponse
    {
        $bookings = Transaction::query()
            ->where('booking_status', 'approved')
            ->with(['details.zoneSpace.zone'])
            ->latest()
            ->get()
            ->map(fn (Transaction $booking) => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'status' => 1,
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
                'guest_name' => $booking->guest_name,
                'payment_method' => $booking->payment_method,
                'total_amount' => (float) $booking->total_amount,
                'details' => $booking->details->map(fn ($detail) => [
                    'zone_space_id' => $detail->zone_space_id,
                    'zone' => $detail->zoneSpace?->zone?->name,
                    'space' => $detail->zoneSpace?->name,
                    'start_time' => $detail->start_time?->toISOString(),
                    'end_time' => $detail->end_time?->toISOString(),
                ])->values(),
            ])->values();

        return response()->json([
            'success' => true,
            'status' => 1,
            'message' => 'Approved bookings',
            'data' => $bookings,
        ]);
    }
}
