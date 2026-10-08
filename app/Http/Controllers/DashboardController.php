<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Transaction;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $today = Carbon::today();
        $nowTime = Carbon::now()->format('H:i');

        $stats = [
            'revenue_today' => (float) Transaction::whereDate('created_at', $today)
                ->where('payment_status', 'paid')->sum('amount'),
            'transactions_today' => Transaction::whereDate('created_at', $today)->count(),
            'bookings_today' => Booking::whereDate('date', $today)
                ->whereNotIn('status', ['cancelled'])->count(),
            'active_members' => UserMembership::where('status', '!=', 'cancelled')
                ->whereDate('end_date', '>=', $today)->count(),
        ];

        $revenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);

            $revenue[] = [
                'label' => $day->translatedFormat('d M'),
                'total' => (float) Transaction::whereDate('created_at', $day)
                    ->where('payment_status', 'paid')->sum('amount'),
            ];
        }

        $bookingStatus = [
            'pending' => Booking::whereDate('date', $today)->where('status', 'pending')->count(),
            'confirmed' => Booking::whereDate('date', $today)->where('status', 'confirmed')->count(),
            'completed' => Booking::whereDate('date', $today)->where('status', 'completed')->count(),
            'cancelled' => Booking::whereDate('date', $today)->where('status', 'cancelled')->count(),
        ];

        $todaysBookings = Booking::with(['court:id,name', 'user:id,name'])
            ->whereDate('date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get();

        $courtMonitor = Court::with('facility:id,name')
            ->orderBy('name')
            ->get()
            ->map(function (Court $court) use ($todaysBookings, $nowTime) {
                $current = $todaysBookings->first(fn (Booking $booking) => $booking->court_id === $court->id
                    && substr((string) $booking->start_time, 0, 5) <= $nowTime
                    && substr((string) $booking->end_time, 0, 5) > $nowTime);

                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'facility' => $court->facility?->name,
                    'status' => $court->status,
                    'in_use' => (bool) $current,
                    'current' => $current ? [
                        'customer' => $current->user?->name ?? $current->guest_name ?? 'Umum',
                        'start_time' => substr((string) $current->start_time, 0, 5),
                        'end_time' => substr((string) $current->end_time, 0, 5),
                    ] : null,
                ];
            })
            ->values();

        $stats['courts_in_use'] = $courtMonitor->where('in_use', true)->count();
        $stats['courts_total'] = $courtMonitor->count();

        $bookings = $todaysBookings->map(fn (Booking $booking) => [
            'id' => $booking->id,
            'court' => $booking->court?->name,
            'customer' => $booking->user?->name ?? $booking->guest_name ?? 'Umum',
            'start_time' => substr((string) $booking->start_time, 0, 5),
            'end_time' => substr((string) $booking->end_time, 0, 5),
            'status' => $booking->status,
        ])->values();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'revenue' => $revenue,
            'bookingStatus' => $bookingStatus,
            'courtMonitor' => $courtMonitor,
            'bookings' => $bookings,
        ]);
    }
}
