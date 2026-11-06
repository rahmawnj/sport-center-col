<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MemberTrainerPackage;
use App\Models\Transaction;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class MemberDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = today();

        $bookings = Booking::query()
            ->with(['court:id,name,facility_id', 'court.facility:id,name', 'trainerAddon.trainer'])
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
            ->get(['id', 'invoice_number', 'guest_name', 'user_id', 'total_amount', 'payment_method', 'payment_status', 'created_at']);

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
                    ->whereDate('start_date', '<=', $today)
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

        $trainerPackages = MemberTrainerPackage::query()
            ->with(['trainerPackage:id,name,total_sessions,duration_days', 'trainer:id,name,specialty', 'facility:id,name'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10, ['*'], 'trainer_packages_page')
            ->withQueryString()
            ->through(fn (MemberTrainerPackage $item) => [
                'id' => $item->id,
                'package' => $item->trainerPackage?->name ?? 'Paket personal trainer',
                'trainer' => $item->trainer?->name ?? 'Trainer belum tersedia',
                'specialty' => $item->trainer?->specialty,
                'facility' => $item->facility?->name,
                'start_date' => $item->start_date?->toDateString(),
                'expires_at' => $item->expires_at?->toDateString(),
                'total_sessions' => (int) $item->total_sessions,
                'used_sessions' => (int) $item->used_sessions,
                'remaining_sessions' => $item->remaining_sessions,
                'status' => $item->status,
            ]);

        return Inertia::render('member/Membership', [
            'memberships' => $memberships,
            'trainerPackages' => $trainerPackages,
        ]);
    }


    public function membershipShow(Request $request, UserMembership $membership): Response
    {
        abort_unless($membership->user_id === $request->user()->id, 404);

        $membership->load(['package:id,name,type,duration_days,duration_minutes,facility_id', 'package.facility:id,name']);

        return Inertia::render('member/MembershipShow', [
            'membership' => [
                'id' => $membership->id,
                'package' => $membership->package?->name ?? 'Membership',
                'package_type' => $membership->package?->type,
                'facility' => $membership->package?->facility?->name,
                'start_date' => $membership->start_date?->toDateString(),
                'end_date' => $membership->end_date?->toDateString(),
                'duration_days' => $membership->package?->duration_days,
                'duration_minutes' => $membership->package?->duration_minutes,
                'status' => $this->membershipRow($membership)['status'],
                'registered_at' => $membership->created_at?->toISOString(),
            ],
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
                'invoice_number' => $transaction->invoice_number,
                'reference' => $transaction->transactionable_type === 'membership' ? 'Membership + Personal Trainer' : 'Booking',
                'amount' => (float) $transaction->total_amount,
                'payment_method' => $transaction->payment_method,
                'payment_status' => $transaction->payment_status,
                'created_at' => $transaction->created_at?->toISOString(),
            ]);

        return Inertia::render('member/Payments', ['payments' => $payments]);
    }

    public function paymentShow(Request $request, Transaction $transaction): Response
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);

        return Inertia::render('member/PaymentShow', [
            'payment' => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'customer_name' => $transaction->guest_name,
                'transaction_type' => $transaction->transactionable_type,
                'amount' => (float) $transaction->total_amount,
                'payment_method' => $transaction->payment_method,
                'payment_status' => $transaction->payment_status,
                'booking_status' => null,
                'created_at' => $transaction->created_at?->toISOString(),
            ],
        ]);
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
                'face_photo_url' => $user->memberProfile?->face_photo_path ? Storage::disk('public')->url($user->memberProfile->face_photo_path) : null,
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
            'trainer' => $booking->trainerAddon?->trainer?->name,
            'trainer_addon_price' => (float) ($booking->trainerAddon?->price ?? 0),
            'status' => $booking->status,
            'scan_count' => (int) $booking->scan_count,
        ];
    }

    private function membershipRow(UserMembership $membership): array
    {
        $cancelled = $membership->status === 'cancelled';
        $scheduled = ! $cancelled && $membership->start_date && $membership->start_date->gt(today());
        $expired = ! $cancelled && ! $scheduled && $membership->end_date && $membership->end_date->lt(today());

        return [
            'id' => $membership->id,
            'package' => $membership->package?->name ?? 'Membership',
            'facility' => $membership->package?->facility?->name,
            'start_date' => $membership->start_date?->toDateString(),
            'end_date' => $membership->end_date?->toDateString(),
            'duration_days' => $membership->package?->duration_days,
            'status' => $cancelled ? 'cancelled' : ($scheduled ? 'scheduled' : ($expired ? 'expired' : 'active')),
            'scan_count' => (int) $membership->scan_count,
        ];
    }
}
