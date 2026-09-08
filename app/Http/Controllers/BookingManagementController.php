<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BookingManagementController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function index(Request $request): Response
    {
        $this->authorizeAdmin();

        $status = $request->input('status', 'all');
        $paymentStatus = $request->input('payment_status', 'all');
        $search = trim((string) $request->input('search', ''));

        $bookings = Transaction::query()
            ->with([
                'details.zoneSpace.zone',
                'paymentProofs',
            ])
            ->when($status !== 'all', fn ($query) => $query->where('booking_status', $status))
            ->when($paymentStatus !== 'all', fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) =>
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhere('guest_name', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Transaction $booking) => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'guest_name' => $booking->guest_name,
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
                'payment_method' => $booking->payment_method,
                'total_amount' => (float) $booking->total_amount,
                'created_at' => $booking->created_at?->toISOString(),
                'details' => $booking->details->map(fn ($detail) => [
                    'zone_space' => $detail->zoneSpace?->name,
                    'zone' => $detail->zoneSpace?->zone?->name,
                    'start_time' => $detail->start_time?->toISOString(),
                    'end_time' => $detail->end_time?->toISOString(),
                ])->values(),
                'payment_proofs' => $booking->paymentProofs->map(fn ($proof) => [
                    'id' => $proof->id,
                    'payment_option' => $proof->payment_option,
                    'amount_paid' => (float) $proof->amount_paid,
                    'proof_url' => $proof->proof_path ? Storage::url($proof->proof_path) : null,
                ])->values(),
            ]);

        return Inertia::render('bookings/Index', [
            'bookings' => $bookings,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'payment_status' => $paymentStatus,
            ],
            'stats' => [
                'pending' => Transaction::where('booking_status', 'pending')->count(),
                'approved' => Transaction::where('booking_status', 'approved')->count(),
                'payment_pending' => Transaction::whereIn('payment_status', ['unpaid', 'dp_paid'])->count(),
                'total' => Transaction::count(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'booking_status' => ['required', 'in:pending,approved,rejected,cancelled'],
        ]);

        $transaction->update([
            'booking_status' => $data['booking_status'],
            'handled_by' => auth()->id(),
        ]);

        return back()->with('success', "Status booking {$transaction->booking_code} berhasil diperbarui.");
    }

    public function updatePaymentStatus(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'payment_status' => ['required', 'in:unpaid,dp_paid,fully_paid'],
        ]);

        $transaction->update([
            'payment_status' => $data['payment_status'],
            'handled_by' => auth()->id(),
        ]);

        return back()->with('success', "Status pembayaran {$transaction->booking_code} berhasil diperbarui.");
    }
}
