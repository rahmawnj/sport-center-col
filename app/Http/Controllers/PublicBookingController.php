<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionPaymentProof;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PublicBookingController extends Controller
{
    public function index(Request $request): Response
    {
        $zones = Zone::query()
            ->where('is_online_bookable', true)
            ->with(['zoneSpaces' => fn ($query) => $query
                ->where('status', 'available')
                ->with(['facilities:id,name'])
                ->with(['pricingRates' => fn ($rateQuery) => $rateQuery->select('id', 'zone_space_id', 'rental_type', 'price')])
                ->orderBy('name')
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'pricing_model', 'is_online_bookable']);

        return Inertia::render('booking/Index', [
            'zones' => $zones,
            'initial' => [
                'date' => $request->input('date'),
                'zone_id' => $request->input('zone_id'),
            ],
        ]);
    }

    public function payment(): Response
    {
        return Inertia::render('booking/Payment');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'zone_space_id' => ['required', 'integer', 'exists:zone_spaces,id'],
            'guest_name' => ['required', 'string', 'max:255'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'payment_option' => ['required', 'in:full_payment,half_payment,pay_later'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            $space = \App\Models\ZoneSpace::query()
                ->where('id', $data['zone_space_id'])
                ->where('status', 'available')
                ->whereHas('zone', fn ($query) => $query->where('is_online_bookable', true))
                ->with(['zone:id,name', 'pricingRates:id,zone_space_id,rental_type,price'])
                ->first();

            if (!$space) {
                throw ValidationException::withMessages([
                    'zone_space_id' => 'Space yang dipilih tidak tersedia untuk online booking.',
                ]);
            }

            $rate = $space->pricingRates->first();
            if (!$rate) {
                throw ValidationException::withMessages([
                    'zone_space_id' => 'Harga booking belum tersedia untuk space ini.',
                ]);
            }

            $start = Carbon::createFromFormat('Y-m-d H:i', $data['booking_date'].' '.$data['start_time']);
            $end = $start->copy()->addHour();

            $alreadyBooked = Transaction::query()
                ->whereIn('booking_status', ['pending', 'approved'])
                ->whereHas('details', fn ($query) => $query
                    ->where('zone_space_id', $space->id)
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start))
                ->exists();

            if ($alreadyBooked) {
                throw ValidationException::withMessages([
                    'start_time' => 'Jadwal tersebut sudah dipesan atau sedang menunggu persetujuan admin.',
                ]);
            }

            $total = (float) $rate->price;
            $amountPaid = match ($data['payment_option']) {
                'full_payment' => $total,
                'half_payment' => $total / 2,
                default => 0,
            };

            $paymentStatus = match ($data['payment_option']) {
                'full_payment' => 'fully_paid',
                'half_payment' => 'dp_paid',
                default => 'unpaid',
            };

            if ($data['payment_option'] !== 'pay_later' && !$request->hasFile('payment_proof')) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Bukti pembayaran wajib diupload untuk pilihan pembayaran ini.',
                ]);
            }

            $transaction = DB::transaction(function () use ($data, $space, $rate, $start, $end, $total, $paymentStatus, $amountPaid, $request) {
                $bookingCode = 'BK-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

                $transaction = new Transaction();
                $transaction->booking_code = $bookingCode;
                $transaction->customer_type = 'general';
                $transaction->guest_name = $data['guest_name'];
                $transaction->payment_method = 'bank_transfer';
                $transaction->total_amount = $total;
                $transaction->payment_status = $paymentStatus;
                $transaction->booking_status = 'pending';
                $transaction->save();

                $transaction->details()->create([
                    'zone_space_id' => $space->id,
                    'qty' => 1,
                    'start_time' => $start,
                    'end_time' => $end,
                    'price_rate' => $rate->price,
                    'subtotal' => $total,
                ]);

                $proofPath = $request->hasFile('payment_proof')
                    ? $request->file('payment_proof')->store('payment-proofs', 'public')
                    : null;

                TransactionPaymentProof::create([
                    'transaction_id' => $transaction->id,
                    'payment_option' => $data['payment_option'],
                    'amount_paid' => $amountPaid,
                    'proof_path' => $proofPath,
                ]);

                return $transaction;
            });

            return redirect()->route('booking.ticket', ['transaction' => $transaction->booking_code]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'booking' => 'Booking gagal disimpan. Periksa database dan konfigurasi server, lalu coba lagi.',
            ])->withInput();
        }
    }
}
