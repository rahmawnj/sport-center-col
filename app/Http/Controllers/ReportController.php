<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * @var array<string, array{label: string, class: class-string}>
     */
    private const TYPES = [
        'package' => ['label' => 'Penjualan Paket', 'class' => Package::class],
        'booking' => ['label' => 'Booking Lapangan', 'class' => Booking::class],
        'membership' => ['label' => 'Membership', 'class' => UserMembership::class],
    ];

    public function index(Request $request, ?string $type = null): Response
    {
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
            'paid' => (float) (clone $base)->where('payment_status', 'paid')->sum('amount'),
            'pending' => (float) (clone $base)->where('payment_status', 'pending')->sum('amount'),
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
                'payment_status' => $transaction->payment_status,
                'created_at' => $transaction->created_at?->toISOString(),
            ]);

        return Inertia::render('reports/Index', [
            'type' => $type,
            'types' => collect(self::TYPES)
                ->map(fn ($meta, $key) => ['value' => $key, 'label' => $meta['label']])
                ->values(),
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }
}
