<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Transaction;
use App\Models\SessionParticipant;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        // A successful sale must be fully paid and accepted by the payment reviewer.
        $successfulTransactions = Transaction::query()
            ->where('payment_status', 'fully_paid')
            ->where('payment_review_status', 'accepted')
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to));

        $summary = [
            'transaction_count' => (clone $successfulTransactions)->count(),
            'revenue' => (float) (clone $successfulTransactions)->sum('total_amount'),
            'average_transaction' => (float) ((clone $successfulTransactions)->avg('total_amount') ?? 0),
        ];

        $typeLabels = [
            'membership' => 'Membership',
            'trainer' => 'Paket Trainer',
            'booking' => 'Booking Lapangan',
            'class' => 'Kelas',
        ];

        $typeRows = (clone $successfulTransactions)
            ->selectRaw("COALESCE(NULLIF(transactionable_type, ''), 'other') as type, COUNT(*) as transaction_count, SUM(total_amount) as revenue")
            ->groupBy('type')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'label' => $typeLabels[$row->type] ?? ($row->type === 'other' ? 'Lainnya' : ucfirst(str_replace('_', ' ', $row->type))),
                'transaction_count' => (int) $row->transaction_count,
                'revenue' => (float) $row->revenue,
            ])
            ->values();

        $transactions = (clone $successfulTransactions)
            ->with('user:id,name')
            ->latest('created_at')
            ->limit(15)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'customer' => $transaction->user?->name ?? $transaction->guest_name ?? 'Pelanggan umum',
                'type' => $transaction->transactionable_type,
                'type_label' => $typeLabels[$transaction->transactionable_type] ?? ($transaction->transactionable_type ? ucfirst(str_replace('_', ' ', $transaction->transactionable_type)) : 'Lainnya'),
                'payment_method' => $transaction->payment_method,
                'amount' => (float) $transaction->total_amount,
                'created_at' => $transaction->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Dashboard', [
            'summary' => $summary,
            'typeRows' => $typeRows,
            'transactions' => $transactions,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function monitoring(Request $request): Response
    {
        $today = Carbon::today();
        $nowTime = Carbon::now()->format('H:i');

        $todaysBookings = Booking::with(['court:id,name', 'user:id,name'])
            ->whereDate('date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get();

        $todaysClasses = SessionParticipant::query()
            ->with([
                'user:id,name',
                'schedule.sportClass.facility:id,name',
                'schedule.trainer:id,name',
                'transaction:id,payment_status,invoice_number',
            ])
            ->whereDate('session_date', $today)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->map(fn (SessionParticipant $participant) => [
                'id' => $participant->id,
                'class_name' => $participant->schedule?->sportClass?->name ?? 'Kelas',
                'facility' => $participant->schedule?->sportClass?->facility?->name,
                'trainer' => $participant->schedule?->trainer?->name,
                'customer' => $participant->user?->name ?? $participant->guest_name ?? 'Peserta',
                'start_time' => substr((string) $participant->schedule?->start_time, 0, 5),
                'end_time' => substr((string) $participant->schedule?->end_time, 0, 5),
                'status' => $participant->status,
                'payment_status' => $participant->transaction?->payment_status,
                'invoice_number' => $participant->transaction?->invoice_number,
            ])
            ->sortBy('start_time')
            ->values();

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

        $bookings = $todaysBookings->map(fn (Booking $booking) => [
            'id' => $booking->id,
            'court' => $booking->court?->name,
            'customer' => $booking->user?->name ?? $booking->guest_name ?? 'Umum',
            'start_time' => substr((string) $booking->start_time, 0, 5),
            'end_time' => substr((string) $booking->end_time, 0, 5),
            'status' => $booking->status,
        ])->values();

        $recentTransactions = Transaction::with('user:id,name')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'customer' => $transaction->user?->name ?? $transaction->guest_name ?? 'Pelanggan umum',
                'amount' => (float) $transaction->total_amount,
                'payment_status' => $transaction->payment_status,
                'created_at' => $transaction->created_at?->format('H:i:s'),
            ]);

        return Inertia::render('Monitoring', [
            'generatedAt' => now()->format('H:i:s'),
            'courtMonitor' => $courtMonitor,
            'bookings' => $bookings,
            'todaysClasses' => $todaysClasses,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
