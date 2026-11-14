<?php

namespace App\\Http\\Controllers;

use App\\Models\\Booking;
use App\\Models\\MemberTrainerPackage;
use App\\Models\\SessionParticipant;
use App\\Models\\Transaction;
use App\\Models\\UserMembership;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Inertia\\Inertia;
use Inertia\\Response;

class ReportController extends Controller
{
    private const TYPES = [
        'membership' => 'Pembelian Membership',
        'trainer' => 'Pembelian Paket Trainer',
        'booking' => 'Pembelian Booking Lapangan',
        'class' => 'Pembelian Kelas',
    ];

    public function index(Request $request, ?string $type = null): Response
    {
        $type ??= 'membership';
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        return match ($type) {
            'membership' => $this->membershipReport($type, $from, $to),
            'trainer' => $this->trainerReport($type, $from, $to),
            'booking' => $this->bookingReport($type, $from, $to),
            'class' => $this->classReport($type, $from, $to),
        };
    }

    private function membershipReport(string $type, ?string $from, ?string $to): Response
    {
        $base = UserMembership::query()
            ->with([
                'user.memberProfile',
                'package.facility',
                'transaction',
            ])
            ->when($from || $to, fn (Builder $query) => $query->where(function (Builder $query) use ($from, $to) {
                $query->whereHas('transaction', fn (Builder $transaction) => $this->applyDateFilter($transaction, $from, $to))
                    ->orWhere(function (Builder $legacy) use ($from, $to) {
                        $legacy->whereDoesntHave('transaction');
                        $this->applyDateFilter($legacy, $from, $to, 'user_memberships.created_at');
                    });
            }));

        $aggregate = DB::table('user_memberships')
            ->leftJoin('transactions', 'transactions.id', '=', 'user_memberships.transaction_id')
            ->leftJoin('packages', 'packages.id', '=', 'user_memberships.package_id');
        $this->applyJoinedDateFilter($aggregate, $from, $to, 'COALESCE(transactions.created_at, user_memberships.created_at)');
        $paymentStatus = "COALESCE(transactions.payment_status, CASE WHEN user_memberships.status = 'active' THEN 'fully_paid' ELSE 'unpaid' END)";
        $summary = [
            'count' => (clone $aggregate)->count('user_memberships.id'),
            'paid' => (float) (clone $aggregate)->whereRaw("$paymentStatus = 'fully_paid'")->sum('packages.price'),
            'pending' => (float) (clone $aggregate)->whereRaw("$paymentStatus IN ('unpaid', 'dp_paid')")->sum('packages.price'),
        ];

        $rows = $base->latest('user_memberships.created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (UserMembership $membership) => [
                'id' => $membership->id,
                'transaction_id' => $membership->transaction?->id,
                'invoice_number' => $membership->transaction?->invoice_number,
                'customer_name' => $membership->user?->name ?? $membership->transaction?->guest_name ?? '—',
                'member_code' => $membership->user?->memberProfile?->member_code,
                'package_name' => $membership->package?->name ?? '—',
                'facility_name' => $membership->package?->facility?->name ?? '—',
                'start_date' => $membership->start_date?->toDateString(),
                'end_date' => $membership->end_date?->toDateString(),
                'membership_status' => $membership->status,
                'payment_status' => $this->paymentStatus($membership->transaction?->payment_status ?? ($membership->status === 'active' ? 'fully_paid' : 'unpaid')),
                'payment_method' => $membership->transaction?->payment_method,
                'amount' => (float) ($membership->package?->price ?? 0),
                'created_at' => ($membership->transaction?->created_at ?? $membership->created_at)?->toISOString(),
            ]);

        return $this->render($type, $rows, $summary, $from, $to);
    }

    private function trainerReport(string $type, ?string $from, ?string $to): Response
    {
        $base = MemberTrainerPackage::query()
            ->with(['transaction', 'trainer', 'trainerPackage', 'facility', 'user.memberProfile'])
            ->whereHas('transaction', fn (Builder $query) => $this->applyDateFilter($query, $from, $to));

        $aggregate = DB::table('member_trainer_packages')
            ->join('transactions', 'transactions.id', '=', 'member_trainer_packages.transaction_id')
            ->leftJoin('trainer_packages', 'trainer_packages.id', '=', 'member_trainer_packages.trainer_package_id');
        $this->applyJoinedDateFilter($aggregate, $from, $to, 'transactions.created_at');
        $summary = [
            'count' => (clone $aggregate)->count('member_trainer_packages.id'),
            'paid' => (float) (clone $aggregate)->where('transactions.payment_status', 'fully_paid')->sum('trainer_packages.price'),
            'pending' => (float) (clone $aggregate)->whereIn('transactions.payment_status', ['unpaid', 'dp_paid'])->sum('trainer_packages.price'),
        ];

        $rows = $base->latest('member_trainer_packages.created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (MemberTrainerPackage $item) => [
                'id' => $item->id,
                'transaction_id' => $item->transaction?->id,
                'invoice_number' => $item->transaction?->invoice_number,
                'customer_name' => $item->user?->name ?? '—',
                'member_code' => $item->user?->memberProfile?->member_code,
                'trainer_name' => $item->trainer?->name ?? '—',
                'package_name' => $item->trainerPackage?->name ?? '—',
                'facility_name' => $item->facility?->name ?? '—',
                'purchase_status' => $item->status,
                'payment_status' => $this->paymentStatus($item->transaction?->payment_status),
                'payment_method' => $item->transaction?->payment_method,
                'amount' => (float) ($item->trainerPackage?->price ?? 0),
                'created_at' => ($item->transaction?->created_at ?? $item->created_at)?->toISOString(),
            ]);

        return $this->render($type, $rows, $summary, $from, $to);
    }

    private function bookingReport(string $type, ?string $from, ?string $to): Response
    {
        $base = Booking::query()
            ->with(['court.facility', 'user.memberProfile'])
            ->when($from, fn (Builder $query) => $query->whereDate('bookings.created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('bookings.created_at', '<=', $to));

        $bookingIds = (clone $base)->pluck('bookings.id');
        $invoiceNumbers = $bookingIds->map(fn ($id) => 'BK-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT));
        $transactions = Transaction::query()
            ->whereIn('invoice_number', $invoiceNumbers)
            ->get()
            ->keyBy('invoice_number');

        $summary = [
            'count' => (clone $base)->count(),
            'paid' => (float) $transactions->where('payment_status', 'fully_paid')->sum('total_amount'),
            'pending' => (float) $transactions->whereIn('payment_status', ['unpaid', 'dp_paid'])->sum('total_amount'),
        ];

        $rows = $base->latest('bookings.created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Booking $booking) use ($transactions) {
                $invoice = 'BK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
                $transaction = $transactions->get($invoice);

                return [
                    'id' => $booking->id,
                    'transaction_id' => $transaction?->id,
                    'invoice_number' => $transaction?->invoice_number ?? $invoice,
                    'customer_name' => $booking->user?->name ?? $booking->guest_name ?? 'Umum',
                    'member_code' => $booking->user?->memberProfile?->member_code,
                    'facility_name' => $booking->court?->facility?->name ?? '—',
                    'court_name' => $booking->court?->name ?? '—',
                    'booking_date' => $booking->date?->toDateString(),
                    'start_time' => substr((string) $booking->start_time, 0, 5),
                    'end_time' => substr((string) $booking->end_time, 0, 5),
                    'booking_status' => $booking->status,
                    'payment_status' => $this->paymentStatus($transaction?->payment_status),
                    'payment_method' => $transaction?->payment_method,
                    'amount' => (float) ($transaction?->total_amount ?? 0),
                    'created_at' => $booking->created_at?->toISOString(),
                ];
            });

        return $this->render($type, $rows, $summary, $from, $to);
    }

    private function classReport(string $type, ?string $from, ?string $to): Response
    {
        $base = SessionParticipant::query()
            ->with([
                'user.memberProfile',
                'schedule.sportClass.facility',
                'schedule.trainer',
                'transaction',
            ])
            ->whereHas('transaction', function (Builder $query) use ($from, $to) {
                $query->where('transactionable_type', 'class');
                $this->applyDateFilter($query, $from, $to);
            });

        $aggregate = DB::table('session_participants')
            ->join('transactions', 'transactions.id', '=', 'session_participants.transaction_id');
        $this->applyJoinedDateFilter($aggregate, $from, $to, 'transactions.created_at');
        $aggregate->where('transactions.transactionable_type', 'class');
        $summary = [
            'count' => (clone $aggregate)->count('session_participants.id'),
            'paid' => (float) (clone $aggregate)->where('transactions.payment_status', 'fully_paid')->sum('session_participants.price'),
            'pending' => (float) (clone $aggregate)->whereIn('transactions.payment_status', ['unpaid', 'dp_paid'])->sum('session_participants.price'),
        ];

        $rows = $base->latest('session_participants.created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (SessionParticipant $participant) => [
                'id' => $participant->id,
                'transaction_id' => $participant->transaction?->id,
                'invoice_number' => $participant->transaction?->invoice_number,
                'customer_name' => $participant->user?->name ?? $participant->guest_name ?? $participant->transaction?->guest_name ?? 'Umum',
                'member_code' => $participant->user?->memberProfile?->member_code,
                'class_name' => $participant->schedule?->sportClass?->name ?? '—',
                'facility_name' => $participant->schedule?->sportClass?->facility?->name ?? '—',
                'trainer_name' => $participant->schedule?->trainer?->name ?? '—',
                'session_date' => $participant->session_date?->toDateString(),
                'start_time' => substr((string) $participant->schedule?->start_time, 0, 5),
                'end_time' => substr((string) $participant->schedule?->end_time, 0, 5),
                'participant_status' => $participant->status,
                'payment_status' => $this->paymentStatus($participant->transaction?->payment_status),
                'payment_method' => $participant->transaction?->payment_method,
                'amount' => (float) $participant->price,
                'created_at' => ($participant->transaction?->created_at ?? $participant->created_at)?->toISOString(),
            ]);

        return $this->render($type, $rows, $summary, $from, $to);
    }

    private function render(string $type, $rows, array $summary, ?string $from, ?string $to): Response
    {
        return Inertia::render('reports/Index', [
            'type' => $type,
            'types' => collect(self::TYPES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'transactions' => $rows,
            'summary' => $summary,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    private function applyDateFilter(Builder $query, ?string $from, ?string $to, string $column = 'created_at'): void
    {
        $query->when($from, fn (Builder $query) => $query->whereDate($column, '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate($column, '<=', $to));
    }

    private function applyJoinedDateFilter($query, ?string $from, ?string $to, string $column): void
    {
        $query->when($from, fn ($query) => $query->whereRaw("DATE($column) >= ?", [$from]))
            ->when($to, fn ($query) => $query->whereRaw("DATE($column) <= ?", [$to]));
    }

    private function paymentStatus(?string $status): string
    {
        return match ($status) {
            'fully_paid', 'paid' => 'paid',
            'dp_paid' => 'partial',
            'unpaid', null => 'pending',
            default => $status,
        };
    }
}
