<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MemberTrainerPackage;
use App\Models\Transaction;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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

        $currentMembership = UserMembership::query()
            ->with(['package:id,name,duration_days,facility_id', 'package.facility:id,name'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->latest('start_date')
            ->first();

        $upcomingMemberships = UserMembership::query()
            ->with(['package:id,name,duration_days,facility_id', 'package.facility:id,name'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereDate('start_date', '>', $today)
            ->orderBy('start_date')
            ->get()
            ->map(fn (UserMembership $membership) => [
                'id' => $membership->id,
                'package' => $membership->package?->name ?? 'Membership',
                'facility' => $membership->package?->facility?->name,
                'start_date' => $membership->start_date?->toDateString(),
                'end_date' => $membership->end_date?->toDateString(),
                'duration_days' => $membership->package?->duration_days,
            ]);

        $activeTrainerPackages = MemberTrainerPackage::query()
            ->with(['trainerPackage:id,name,total_sessions,duration_days', 'trainer:id,name,specialty', 'facility:id,name'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('expires_at', '>=', $today)
            ->whereColumn('used_sessions', '<', 'total_sessions')
            ->latest('start_date')
            ->get()
            ->map(fn (MemberTrainerPackage $item) => [
                'id' => $item->id,
                'package' => $item->trainerPackage?->name ?? 'Paket personal trainer',
                'trainer' => $item->trainer?->name ?? 'Trainer belum ditentukan',
                'specialty' => $item->trainer?->specialty,
                'facility' => $item->facility?->name,
                'start_date' => $item->start_date?->toDateString(),
                'expires_at' => $item->expires_at?->toDateString(),
                'total_sessions' => (int) $item->total_sessions,
                'used_sessions' => (int) $item->used_sessions,
                'remaining_sessions' => $item->remaining_sessions,
            ]);

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
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)->count(),
                'payments_count' => $transactions->count(),
                'payments_total' => (float) $transactions->where('payment_status', 'fully_paid')->sum('total_amount'),
            ],
            'bookings' => $bookings,
            'memberships' => $memberships,
            'currentMembership' => $currentMembership ? [
                'id' => $currentMembership->id,
                'package' => $currentMembership->package?->name ?? 'Membership',
                'facility' => $currentMembership->package?->facility?->name,
                'start_date' => $currentMembership->start_date?->toDateString(),
                'end_date' => $currentMembership->end_date?->toDateString(),
                'duration_days' => $currentMembership->package?->duration_days,
            ] : null,
            'upcomingMemberships' => $upcomingMemberships,
            'activeTrainerPackages' => $activeTrainerPackages,
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
                'reference' => match ($transaction->transactionable_type) { 'membership' => 'Membership + Personal Trainer', 'trainer_package' => 'Personal Trainer', default => 'Booking' },
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
                'can_cancel' => $transaction->payment_status === 'unpaid',
                'cancelled_at' => $transaction->cancelled_at?->toISOString(),
                'booking_status' => null,
                'created_at' => $transaction->created_at?->toISOString(),
            ],
        ]);
    }

    public function cancelPayment(Request $request, Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);

        DB::transaction(function () use ($transaction) {
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            abort_unless($locked->payment_status === 'unpaid', 422, 'Hanya pembayaran yang masih pending yang bisa dibatalkan.');

            $locked->update(['payment_status' => 'failed', 'cancelled_at' => now()]);

            $membershipQuery = UserMembership::query()->where('transaction_id', $locked->id);
            $trainerQuery = MemberTrainerPackage::query()->where('transaction_id', $locked->id);

            // Legacy checkouts created before transaction_id was introduced:
            // only link a single unambiguous child record created close to this invoice.
            $from = $locked->created_at->copy()->subMinutes(5);
            $until = $locked->created_at->copy()->addMinutes(5);

            if (! $membershipQuery->exists() && $locked->transactionable_type === 'membership') {
                $legacyMemberships = UserMembership::query()
                    ->where('user_id', $locked->user_id)
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('created_at', [$from, $until]);
                if ($legacyMemberships->count() === 1) {
                    $legacyMemberships->update(['status' => 'cancelled']);
                }
            } else {
                $membershipQuery->update(['status' => 'cancelled']);
            }

            if ($trainerQuery->exists()) {
                $trainerQuery->where('status', 'pending')->update(['status' => 'cancelled']);
            } elseif (in_array($locked->transactionable_type, ['membership', 'trainer_package'], true)) {
                // Legacy purchases without transaction_id: only cancel a single
                // unambiguous pending trainer package created with this checkout.
                $legacyTrainerPackages = MemberTrainerPackage::query()
                    ->where('user_id', $locked->user_id)
                    ->where('status', 'pending')
                    ->whereBetween('created_at', [$from, $until]);

                if ($legacyTrainerPackages->count() === 1) {
                    $legacyTrainerPackages->update(['status' => 'cancelled']);
                }
            }
        });

        return redirect()->route('member.payments.show', $transaction->id)->with('success', 'Pembayaran berhasil dibatalkan.');
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
        $pending = $membership->status === 'pending';
        $cancelled = $membership->status === 'cancelled';
        $scheduled = ! $pending && ! $cancelled && $membership->start_date && $membership->start_date->gt(today());
        $expired = ! $pending && ! $cancelled && ! $scheduled && $membership->end_date && $membership->end_date->lt(today());

        return [
            'id' => $membership->id,
            'package' => $membership->package?->name ?? 'Membership',
            'facility' => $membership->package?->facility?->name,
            'start_date' => $membership->start_date?->toDateString(),
            'end_date' => $membership->end_date?->toDateString(),
            'duration_days' => $membership->package?->duration_days,
            'status' => $pending ? 'pending' : ($cancelled ? 'cancelled' : ($scheduled ? 'scheduled' : ($expired ? 'expired' : 'active'))),
            'scan_count' => (int) $membership->scan_count,
        ];
    }
}
