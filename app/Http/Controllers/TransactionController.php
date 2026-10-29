<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Device;
use App\Models\Facility;
use App\Models\Package;
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
                ->with(['pricingRules' => fn ($rules) => $rules->orderByDesc('priority')])])
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
                    'pricing_rules' => $package->pricingRules->map(fn ($rule) => [
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
                'payment_status' => $transaction->payment_status,
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
                'device_id' => Device::forFacility($package->facility_id)?->id,
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

        $start = Carbon::today();
        $end = $start->copy()->addDays(max(1, (int) ($package->duration_days ?? 30)));

        DB::transaction(function () use ($package, $data, $start, $end) {
            $membership = UserMembership::create([
                'user_id' => $data['user_id'],
                'package_id' => $package->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'active',
            ]);

            Transaction::create([
                'user_id' => $data['user_id'],
                'device_id' => Device::forFacility($package->facility_id)?->id,
                'customer_name' => $data['customer_name'] ?? null,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'],
            ]);
        });
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
