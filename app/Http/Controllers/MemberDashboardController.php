<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Transaction;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = today();

        $bookings = Booking::query()
            ->with(['court:id,name,facility_id', 'court.facility:id,name'])
            ->where('user_id', $user->id)
            ->latest('date')
            ->latest('start_time')
            ->limit(5)
            ->get()
            ->map(fn (Booking $booking) => $this->bookingRow($booking));

        $memberships = UserMembership::query()
            ->with('package:id,name,duration_days,facility_id', 'package.facility:id,name')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (UserMembership $membership) => $this->membershipRow($membership));

        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'booking_code', 'guest_name', 'user_id', 'total_amount', 'payment_method', 'payment_status', 'created_at']);

        return Inertia::render('member/Dashboard', [
            'stats' => [
                'upcoming_bookings' => Booking::where('user_id', $user->id)
                    ->whereDate('date', '>=', $today)
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->count(),
                'completed_bookings' => Booking::where('user_id', $user->id)
                    ->where('status', 'completed')->count(),
                'active_memberships' => UserMembership::where('user_id', $user->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('end_date', '>=', $today)->count(),
                'payments_count' => $transactions->count(),
                'payments_total' => (float) $transactions->where('payment_status', 'fully_paid')->sum('total_amount'),
            ],
            'bookings' => $bookings,
            'memberships' => $memberships,
        ]);
    }

    public function bookings(Request $request): Response
    {
        $bookings = Booking::query()
            ->with(['court:id,name,facility_id', 'court.facility:id,name'])
            ->where('user_id', $request->user()->id)
            ->latest('date')
            ->latest('start_time')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Booking $booking) => $this->bookingRow($booking));

        return Inertia::render('member/Bookings', ['bookings' => $bookings]);
    }

    public function membership(Request $request): Response
    {
        $memberships = UserMembership::query()
            ->with(['package:id,name,duration_days,facility_id', 'package.facility:id,name'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (UserMembership $membership) => $this->membershipRow($membership));

        return Inertia::render('member/Membership', [
            'memberships' => $memberships,
        ]);
    }

    public function payments(Request $request): Response
    {
        $payments = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'booking_code' => $transaction->booking_code,
                'reference' => match (class_basename((string) $transaction->transactionable_type)) { 'UserMembership' => 'Membership', 'Booking' => 'Booking', 'Package' => 'Package', default => ($transaction->customer_type === 'member' ? 'Transaksi member' : 'Transaksi umum'), },
                'amount' => (float) $transaction->total_amount,
                'payment_method' => $transaction->payment_method,
                'payment_status' => $transaction->payment_status,
                'created_at' => $transaction->created_at?->toISOString(),
            ]);

        return Inertia::render('member/Payments', ['payments' => $payments]);
    }

    public function profile(Request $request): Response
    {
        $user = $request->user()->loadMissing('memberProfile');

        return Inertia::render('member/Profile', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? $user->memberProfile?->phone,
                'member_code' => $user->memberProfile?->member_code,
            ],
        ]);
    }

    private function bookingRow(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'date' => $booking->date?->toDateString(),
            'start_time' => $booking->start_time ? substr((string) $booking->start_time, 0, 5) : null,
            'end_time' => $booking->end_time ? substr((string) $booking->end_time, 0, 5) : null,
            'court' => $booking->court?->name,
            'facility' => $booking->court?->facility?->name,
            'status' => $booking->status,
        ];
    }

    private function membershipRow(UserMembership $membership): array
    {
        $cancelled = $membership->status === 'cancelled';
        $expired = ! $cancelled && $membership->end_date && $membership->end_date->lt(today());

        return [
            'id' => $membership->id,
            'package' => $membership->package?->name ?? 'Membership',
            'facility' => $membership->package?->facility?->name,
            'start_date' => $membership->start_date?->toDateString(),
            'end_date' => $membership->end_date?->toDateString(),
            'duration_days' => $membership->package?->duration_days,
            'status' => $cancelled ? 'cancelled' : ($expired ? 'expired' : 'active'),
        ];
    }
}
