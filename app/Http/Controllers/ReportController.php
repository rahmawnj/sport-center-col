<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MemberTrainerPackage;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    private const TYPES = [
        'package' => ['label' => 'Penjualan Paket', 'class' => Package::class],
        'booking' => ['label' => 'Booking Lapangan', 'class' => Booking::class],
        'membership' => ['label' => 'Membership', 'class' => UserMembership::class],
    ];

    public function index(Request $request, ?string $type = null): Response
    {
        if ($type === 'trainer') {
            return $this->trainerReport($request);
        }

        $type = array_key_exists((string) $type, self::TYPES) ? $type : 'package';
        $class = self::TYPES[$type]['class'];
        $from = $request->query('from');
        $to = $request->query('to');

        $base = Transaction::query()
            ->where('transactionable_type', $class)
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to));

        $summary = [
            'count' => (clone $base)->count(),
            'paid' => (float) (clone $base)->where('payment_status', 'fully_paid')->sum('total_amount'),
            'pending' => (float) (clone $base)->where('payment_status', 'unpaid')->sum('total_amount'),
        ];

        $transactions = (clone $base)
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'customer_name' => $transaction->customer_name,
                'amount' => (float) $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'payment_status' => match ($transaction->payment_status) {
                    'fully_paid' => 'paid',
                    'dp_paid' => 'partial',
                    'unpaid' => 'pending',
                    default => $transaction->payment_status,
                },
                'created_at' => $transaction->created_at?->toISOString(),
            ]);

        return Inertia::render('reports/Index', [
            'type' => $type,
            'types' => collect(self::TYPES)
                ->filter(fn ($meta, $key) => $key !== 'membership')
                ->map(fn ($meta, $key) => ['value' => $key, 'label' => $meta['label']])
                ->values()
                ->push(['value' => 'trainer', 'label' => 'Trainer']),
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    private function trainerReport(Request $request): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $base = MemberTrainerPackage::query()
            ->with(['transaction', 'trainer', 'trainerPackage', 'facility', 'user'])
            ->whereHas('transaction', fn ($query) => $query
                ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
                ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to)));

        $paidItems = (clone $base)->whereHas('transaction', fn ($query) => $query->where('payment_status', 'fully_paid'))->with('trainerPackage')->get();
        $pendingItems = (clone $base)->whereHas('transaction', fn ($query) => $query->where('payment_status', 'unpaid'))->with('trainerPackage')->get();

        $summary = [
            'count' => (clone $base)->count(),
            'paid' => (float) $paidItems->sum(fn (MemberTrainerPackage $item) => (float) ($item->trainerPackage?->price ?? 0)),
            'pending' => (float) $pendingItems->sum(fn (MemberTrainerPackage $item) => (float) ($item->trainerPackage?->price ?? 0)),
        ];

        $transactions = $base
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(function (MemberTrainerPackage $item) {
                $transaction = $item->transaction;

                return [
                    'id' => $transaction?->id ?? $item->id,
                    'invoice_number' => $transaction?->invoice_number,
                    'customer_name' => $transaction?->customer_name ?? $item->user?->name,
                    'amount' => (float) ($item->trainerPackage?->price ?? 0),
                    'payment_method' => $transaction?->payment_method,
                    'payment_status' => match ($transaction?->payment_status) {
                        'fully_paid' => 'paid',
                        'dp_paid' => 'partial',
                        'unpaid' => 'pending',
                        default => $transaction?->payment_status ?? 'pending',
                    },
                    'created_at' => $transaction?->created_at?->toISOString() ?? $item->created_at?->toISOString(),
                    'trainer_name' => $item->trainer?->name ?? '—',
                    'package_name' => $item->trainerPackage?->name ?? '—',
                    'facility_name' => $item->facility?->name ?? '—',
                ];
            });

        $types = collect(self::TYPES)
            ->filter(fn ($meta, $key) => $key !== 'membership')
            ->map(fn ($meta, $key) => ['value' => $key, 'label' => $meta['label']])
            ->values()
            ->push(['value' => 'trainer', 'label' => 'Trainer']);

        return Inertia::render('reports/Index', [
            'type' => 'trainer',
            'types' => $types,
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }
}
