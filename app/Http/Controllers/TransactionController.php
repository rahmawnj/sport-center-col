<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MemberTrainerPackage;
use App\Models\Device;
use App\Models\Facility;
use App\Models\Package;
use App\Models\PackagePricingRule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');

        $statusMap = ['paid' => 'fully_paid', 'pending' => 'unpaid', 'partial' => 'dp_paid'];
        $dbStatus = $statusMap[$status] ?? null;

        $transactions = Transaction::query()
            ->when($search !== '', fn ($query) => $query->where('guest_name', 'like', "%{$search}%"))
            ->when($dbStatus, fn ($query) => $query->where('payment_status', $dbStatus))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'customer_name' => $transaction->customer_name,
                'reference' => $this->reference($transaction),
                'amount' => (float) $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'payment_status' => $this->normalizeStatus($transaction->payment_status),
                'type' => $transaction->transactionable_type,
                'scan_count' => null,
                'created_at' => $transaction->created_at?->toISOString(),
            ]);

        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'today' => (float) Transaction::whereDate('created_at', today())
                    ->where('payment_status', 'fully_paid')
                    ->sum('total_amount'),
                'total' => Transaction::count(),
                'paid' => Transaction::where('payment_status', 'fully_paid')->count(),
                'pending' => Transaction::where('payment_status', 'unpaid')->count(),
            ],
        ]);
    }

    /**
     * Return the transaction reference displayed in transaction lists and details.
     */
    private function reference(Transaction $transaction): string
    {
        return $transaction->booking_code
            ?: 'TRX-'.str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT);
    }

    private function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'fully_paid' => 'paid',
            'dp_paid' => 'partial',
            'unpaid' => 'pending',
            default => (string) $status,
        };
    }

    public function create(): Response
    {
        $facilities = Facility::query()
            ->where('status', 'active')
            ->with(['packages' => fn ($query) => $query
                ->orderBy('name')
                ->with([
                    'pricingRules' => fn ($rules) => $rules->orderByDesc('priority'),
                ])])
            ->orderBy('name')
            ->get()
            ->map(fn (Facility $facility) => [
                'id' => $facility->id,
                'name' => $facility->name,
                'packages' => $facility->packages->map(fn (Package $package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'type' => $package->type,
                    'duration_minutes' => $package->duration_minutes,
                    'duration_days' => $package->duration_days,
                    'price' => $package->type === 'membership' ? (float) $package->price : null,
                    'pricing_rules' => ($package->type === 'membership' ? collect() : $package->pricingRules)->map(fn ($rule) => [
                        'id' => $rule->id,
                        'label' => $this->ruleLabel($rule),
                        'price' => (float) $rule->price,
                    ])->values(),
                ])->values(),
            ]);

        $members = User::query()
            ->whereHas('memberProfile')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name]);

        return Inertia::render('transactions/Create', [
            'facilities' => $facilities,
            'members' => $members,
        ]);
    }

    public function edit(Transaction $transaction): Response
    {
        return Inertia::render('transactions/Edit', [
            'transaction' => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'customer_name' => $transaction->customer_name,
                'amount' => (float) $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'payment_status' => $this->normalizeStatus($transaction->payment_status),
                'reference' => $this->reference($transaction),
            ],
        ]);
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'payment_status' => ['required', Rule::in(['pending', 'paid', 'partial', 'failed', 'refunded'])],
        ]);

        DB::transaction(function () use ($transaction, $data) {
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);

            $locked->customer_name = $data['customer_name'] ?? null;
            $locked->amount = $data['amount'];
            $locked->payment_method = $data['payment_method'];
            $locked->payment_status = match ($data['payment_status']) {
                'paid' => 'fully_paid',
                'pending' => 'unpaid',
                'partial' => 'dp_paid',
                default => $data['payment_status'],
            };
            $locked->save();

            // Activate/cancel only purchase records belonging to this exact
            // invoice. Never update another pending purchase from the same member.
            $childStatus = match ($locked->payment_status) {
                'fully_paid' => 'active',
                'failed', 'refunded' => 'cancelled',
                default => 'pending',
            };

            if ($locked->transactionable_type === 'membership') {
                $membership = UserMembership::query()
                    ->with('package')
                    ->where('transaction_id', $locked->id)
                    ->whereIn('status', ['pending', 'active'])
                    ->lockForUpdate()
                    ->first();

                if ($membership) {
                    if ($childStatus === 'active' && $membership->status === 'pending') {
                        // Start the membership only when payment is approved.
                        // Paid future memberships remain in the queue.
                        User::query()->lockForUpdate()->findOrFail($locked->user_id);

                        // Gunakan antrean global: membership maupun trainer,
                        // dari fasilitas mana pun, tidak boleh berjalan bersamaan.
                        $purchaseStart = $this->nextPurchaseStartDate($locked->user_id, $membership->id);
                        $start = $purchaseStart;
                        $end = $start->copy()->addDays(max(1, (int) ($membership->package?->duration_days ?? 30)) - 1);

                        $membership->update([
                            'start_date' => $start->toDateString(),
                            'end_date' => $end->toDateString(),
                            'status' => 'active',
                        ]);
                    } else {
                        $membership->update(['status' => $childStatus]);
                    }
                }

                $trainerPurchase = MemberTrainerPackage::query()
                    ->with('trainerPackage')
                    ->where('transaction_id', $locked->id)
                    ->whereIn('status', ['pending', 'active'])
                    ->lockForUpdate()
                    ->first();

                if ($trainerPurchase) {
                    if ($childStatus === 'active' && $trainerPurchase->status === 'pending') {
                        $trainerStart = $purchaseStart ?? $this->nextPurchaseStartDate($locked->user_id, null, $trainerPurchase->id);
                        $trainerEnd = $trainerStart->copy()->addDays(max(1, (int) ($trainerPurchase->trainerPackage?->duration_days ?? 30)) - 1);

                        $trainerPurchase->update([
                            'start_date' => $trainerStart->toDateString(),
                            'expires_at' => $trainerEnd->toDateString(),
                            'status' => 'active',
                        ]);
                    } else {
                        $trainerPurchase->update(['status' => $childStatus]);
                    }
                }
            } elseif ($locked->transactionable_type === 'trainer_package') {
                $trainerPurchase = MemberTrainerPackage::query()
                    ->with('trainerPackage')
                    ->where('transaction_id', $locked->id)
                    ->whereIn('status', ['pending', 'active'])
                    ->lockForUpdate()
                    ->first();

                if ($trainerPurchase) {
                    if ($childStatus === 'active') {
                        $trainerStart = $this->nextPurchaseStartDate($locked->user_id, null, $trainerPurchase->id);
                        $trainerEnd = $trainerStart->copy()->addDays(max(1, (int) ($trainerPurchase->trainerPackage?->duration_days ?? 30)) - 1);

                        $trainerPurchase->update([
                            'start_date' => $trainerStart->toDateString(),
                            'expires_at' => $trainerEnd->toDateString(),
                            'status' => 'active',
                        ]);
                    } else {
                        $trainerPurchase->update(['status' => $childStatus]);
                    }
                }
            }
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function show(Transaction $transaction): Response
    {
        return Inertia::render('transactions/Show', [
            'transaction' => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'qr_code' => $transaction->booking_code,
                'customer_name' => $transaction->customer_name,
                'amount' => (float) $transaction->amount,
                'payment_method' => $transaction->payment_method,
                'payment_status' => $this->normalizeStatus($transaction->payment_status),
                'reference' => $this->reference($transaction),
                'created_at' => $transaction->created_at?->toISOString(),
                'details' => [],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'pricing_rule_id' => ['nullable', 'integer', 'exists:package_pricing_rules,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'payment_status' => ['required', Rule::in(['paid', 'pending'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'court_id' => ['nullable', 'integer', 'exists:courts,id'],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $package = Package::findOrFail($data['package_id']);

        if ($package->type === 'membership') {
            $data['amount'] = (float) $package->price;
        } elseif ($package->type !== 'session') {
            $ruleId = $data['pricing_rule_id'] ?? null;
            $rule = $ruleId
                ? PackagePricingRule::query()->where('package_id', $package->id)->find($ruleId)
                : null;

            if (! $rule) {
                throw ValidationException::withMessages([
                    'pricing_rule_id' => 'Pilih aturan harga yang sesuai dengan paket.',
                ]);
            }

            $data['amount'] = (float) $rule->price * (int) $data['quantity'];
        }

        if ($package->type === 'session') {
            $this->storeBooking($package, $data);

            return redirect()->route('transactions.index')->with(
                'success',
                'Booking berhasil disimpan ke tabel bookings. Transaksi pembayaran belum dibuat.'
            );
        }

        if ($package->type === 'membership') {
            $this->storeMembership($package, $data);
        } else {
            Transaction::create([
                'transactionable_type' => 'booking',
                'customer_name' => $data['customer_name'] ?? null,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'],
            ]);
        }

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil disimpan.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeMembership(Package $package, array $data): void
    {
        if (empty($data['user_id'])) {
            throw ValidationException::withMessages([
                'user_id' => 'Member wajib dipilih untuk paket membership.',
            ]);
        }

        DB::transaction(function () use ($package, $data) {
            // Lock member row to prevent two simultaneous purchases from receiving
            // the same start date.
            $member = User::query()->lockForUpdate()->findOrFail($data['user_id']);
            $paid = $data['payment_status'] === 'paid';

            $transaction = Transaction::create([
                'user_id' => $member->id,
                'transactionable_type' => 'membership',
                'customer_name' => $data['customer_name'] ?? null,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_status' => $paid ? 'fully_paid' : 'unpaid',
            ]);

            // Pembelian manual juga mengikuti antrean global member.
            $start = $this->nextPurchaseStartDate($member->id);

            $end = $start->copy()->addDays(max(1, (int) ($package->duration_days ?? 30)) - 1);

            UserMembership::create([
                'user_id' => $member->id,
                'transaction_id' => $transaction->id,
                'package_id' => $package->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => $paid ? 'active' : 'pending',
            ]);
        });
    }

    /**
     * Ambil tanggal mulai berikutnya dari antrean global membership + trainer.
     * Semua paket aktif milik member diperhitungkan, lintas fasilitas.
     */
    private function nextPurchaseStartDate(int $userId, ?int $excludeMembershipId = null, ?int $excludeTrainerPurchaseId = null): \Carbon\CarbonInterface
    {
        $membershipEnd = UserMembership::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', today())
            ->when($excludeMembershipId, fn ($query) => $query->where('id', '!=', $excludeMembershipId))
            ->lockForUpdate()
            ->max('end_date');

        $trainerEnd = MemberTrainerPackage::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', today())
            ->when($excludeTrainerPurchaseId, fn ($query) => $query->where('id', '!=', $excludeTrainerPurchaseId))
            ->lockForUpdate()
            ->max('expires_at');

        $latestEnd = collect([$membershipEnd, $trainerEnd])
            ->filter()
            ->map(fn ($date) => Carbon::parse($date))
            ->sort()
            ->last();

        return $latestEnd ? $latestEnd->copy()->addDay() : today();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeBooking(Package $package, array $data): void
    {
        if (empty($data['court_id']) || empty($data['date']) || empty($data['start_time'])) {
            throw ValidationException::withMessages([
                'court_id' => 'Lapangan, tanggal, dan jam wajib dipilih untuk paket sesi.',
            ]);
        }

        $package->loadMissing('facility');
        $turnaround = max(0, (int) $package->facility->turnaround_minutes);

        $date = Carbon::parse($data['date']);
        $startMinutes = $this->toMinutes($data['start_time']);
        $endMinutes = $startMinutes + max(1, (int) $package->duration_minutes);
        $startTime = $this->toTime($startMinutes);
        $endTime = $this->toTime($endMinutes);

        if ($date->isToday() && $startMinutes < (now()->hour * 60 + now()->minute)) {
            throw ValidationException::withMessages([
                'start_time' => 'Jam yang dipilih sudah lewat.',
            ]);
        }

        DB::transaction(function () use ($data, $date, $startTime, $endTime, $startMinutes, $endMinutes, $turnaround) {
            $conflict = Booking::query()
                ->where('court_id', $data['court_id'])
                ->whereDate('date', $date->toDateString())
                ->whereNotIn('status', ['cancelled'])
                ->lockForUpdate()
                ->get(['start_time', 'end_time'])
                ->contains(fn (Booking $booking) => $this->windowsOverlap(
                    $startMinutes,
                    $endMinutes,
                    $this->toMinutes((string) $booking->start_time),
                    $this->toMinutes((string) $booking->end_time),
                    $turnaround,
                ));

            if ($conflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'Lapangan sudah terisi pada jam tersebut. Pilih jam atau lapangan lain.',
                ]);
            }

            // For session bookings, save the reservation only.
            // Payment transactions are intentionally not created in this flow.
            Booking::create([
                'guest_name' => $data['customer_name'] ?? null,
                'court_id' => $data['court_id'],
                'device_id' => Device::forCourt($data['court_id'])?->id,
                'date' => $date->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'pending',
            ]);
        });
    }

    private function windowsOverlap(int $start, int $end, int $otherStart, int $otherEnd, int $turnaround): bool
    {
        return $start < ($otherEnd + $turnaround) && ($end + $turnaround) > $otherStart;
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours) * 60 + (int) $minutes;
    }

    private function toTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
    }

    private function ruleLabel($rule): string
    {
        $day = match ($rule->day_type) {
            'weekday' => 'Weekday',
            'weekend' => 'Weekend',
            default => 'Semua hari',
        };

        $time = $rule->start_time && $rule->end_time
            ? substr((string) $rule->start_time, 0, 5).'–'.substr((string) $rule->end_time, 0, 5)
            : 'Seharian';

        return $day.' · '.$time;
    }
}
