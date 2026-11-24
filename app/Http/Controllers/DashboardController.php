<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Transaction;
use App\Models\SessionParticipant;
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

        // Revenue reports include only fully paid transactions accepted by the reviewer.
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

        $categories = [
            'booking' => 'Booking Lapangan',
            'membership' => 'Membership',
            'trainer_package' => 'Paket Trainer',
            'class' => 'Kelas',
        ];

        $categoryAggregates = (clone $successfulTransactions)
            ->selectRaw("COALESCE(NULLIF(transactionable_type, ''), 'other') as type, COUNT(*) as transaction_count, SUM(total_amount) as revenue")
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $categoryRows = collect($categories)->map(function (string $label, string $type) use ($categoryAggregates) {
            $row = $categoryAggregates->get($type);

            return [
                'type' => $type,
                'label' => $label,
                'transaction_count' => (int) ($row?->transaction_count ?? 0),
                'revenue' => (float) ($row?->revenue ?? 0),
            ];
        })->values();

        $dailyRows = (clone $successfulTransactions)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as transaction_count, SUM(total_amount) as revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderByDesc('date')
            ->limit(31)
            ->get()
            ->sortBy('date')
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'transaction_count' => (int) $row->transaction_count,
                'revenue' => (float) $row->revenue,
            ])
            ->values();

        $paymentMethods = (clone $successfulTransactions)
            ->selectRaw("COALESCE(NULLIF(payment_method, ''), 'other') as method, COUNT(*) as transaction_count, SUM(total_amount) as revenue")
            ->groupBy('method')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'label' => match ($row->method) {
                    'cash' => 'Tunai',
                    'bank_transfer' => 'Transfer bank',
                    'qris' => 'QRIS',
                    default => 'Lainnya',
                },
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
                'type_label' => $categories[$transaction->transactionable_type] ?? 'Lainnya',
                'payment_method' => $transaction->payment_method,
                'amount' => (float) $transaction->total_amount,
                'created_at' => $transaction->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Dashboard', [
            'summary' => $summary,
            'categoryRows' => $categoryRows,
            'dailyRows' => $dailyRows,
            'paymentMethods' => $paymentMethods,
            'transactions' => $transactions,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function monitoring(Request $request): Response
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $selectedDate = $filters['date'] ?? Carbon::today()->toDateString();
        $isToday = $selectedDate === Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i');

        $selectedBookings = Booking::with(['court:id,name', 'user:id,name'])
            ->whereDate('date', $selectedDate)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get();

        $selectedClasses = SessionParticipant::query()
            ->with([
                'user:id,name',
                'schedule.sportClass.facility:id,name',
                'schedule.trainer:id,name',
                'transaction:id,payment_status,invoice_number',
            ])
            ->whereDate('session_date', $selectedDate)
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
            ->map(function (Court $court) use ($selectedBookings, $nowTime, $isToday) {
                $current = $isToday
                    ? $selectedBookings->first(fn (Booking $booking) => $booking->court_id === $court->id
                        && substr((string) $booking->start_time, 0, 5) <= $nowTime
                        && substr((string) $booking->end_time, 0, 5) > $nowTime)
                    : null;

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

        $bookings = $selectedBookings->map(fn (Booking $booking) => [
            'id' => $booking->id,
            'court' => $booking->court?->name,
            'customer' => $booking->user?->name ?? $booking->guest_name ?? 'Umum',
            'start_time' => substr((string) $booking->start_time, 0, 5),
            'end_time' => substr((string) $booking->end_time, 0, 5),
            'status' => $booking->status,
        ])->values();

        $recentTransactions = Transaction::with('user:id,name')
            ->whereDate('created_at', $selectedDate)
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
            'selectedDate' => $selectedDate,
            'isToday' => $isToday,
            'courtMonitor' => $courtMonitor,
            'bookings' => $bookings,
            'todaysClasses' => $selectedClasses,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
