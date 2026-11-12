<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingTimeSlot;
use App\Models\ClassSchedule;
use App\Models\MemberTrainerPackage;
use App\Models\Device;
use App\Models\Facility;
use App\Models\Package;
use App\Models\PackagePricingRule;
use App\Models\SessionParticipant;
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
                'has_booking' => (bool) $facility->has_booking,
                'has_session' => (bool) $facility->has_session,
                'has_membership' => (bool) $facility->has_membership,
                'packages' => $facility->packages
                    ->filter(fn (Package $package) => match ($package->type) {
                        'membership' => (bool) $facility->has_membership,
                        'session' => (bool) $facility->has_booking,
                        default => (bool) $facility->has_session,
                    })
                    ->map(fn (Package $package) => [
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

        $classRegistrationCounts = SessionParticipant::query()
            ->select('class_schedule_id', 'session_date', DB::raw('COUNT(*) as registered_count'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->groupBy('class_schedule_id', 'session_date')
            ->get()
            ->groupBy('class_schedule_id')
            ->map(fn ($rows) => $rows->mapWithKeys(fn ($row) => [
                Carbon::parse($row->session_date)->toDateString() => (int) $row->registered_count,
            ]));

        $classSchedules = ClassSchedule::query()
            ->with(['sportClass.facility', 'trainer'])
            ->where('is_active', true)
            ->whereHas('sportClass', fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('facility', fn ($facility) => $facility
                    ->where('status', 'active')
                    ->where('has_session', true)))
            ->withCount(['participants as registered_count' => fn ($query) => $query
                ->whereIn('status', ['pending', 'confirmed'])])
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSchedule $schedule) => [
                'id' => $schedule->id,
                'class_name' => $schedule->sportClass?->name ?? 'Kelas',
                'facility_id' => $schedule->sportClass?->facility_id,
                'day' => $schedule->day,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'trainer' => $schedule->trainer?->name,
                'capacity' => (int) ($schedule->capacity ?? $schedule->sportClass?->default_capacity ?? 1),
                'registered_count' => (int) $schedule->registered_count,
                'registered_by_date' => $classRegistrationCounts->get($schedule->id, collect())->all(),
                'price' => (float) $schedule->price,
            ])->values();

        return Inertia::render('transactions/Create', [
            'facilities' => $facilities,
            'members' => $members,
            'classSchedules' => $classSchedules,
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
            } elseif ($locked->transactionable_type === 'booking') {
                if (preg_match('/^BK-(\\d+)$/', (string) $locked->invoice_number, $matches)) {
                    $booking = Booking::query()->lockForUpdate()->find((int) $matches[1]);
                    if ($booking) {
                        $booking->update([
                            'status' => match ($childStatus) {
                                'active' => 'confirmed',
                                'cancelled' => 'cancelled',
                                default => 'pending',
                            },
                        ]);
                    }
                }
            } elseif ($locked->transactionable_type === 'class') {
                $participants = SessionParticipant::query()
                    ->with('schedule')
                    ->where('transaction_id', $locked->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->lockForUpdate()
                    ->get();

                $hasCancelledParticipant = SessionParticipant::query()
                    ->where('transaction_id', $locked->id)
                    ->where('status', 'cancelled')
                    ->exists();

                if ($childStatus === 'active' && (
                    $participants->isEmpty()
                    || $hasCancelledParticipant
                    || $participants->contains(fn (SessionParticipant $participant) => ! $participant->schedule?->is_active
                        || $participant->session_date?->lt(today())
                        || ($participant->session_date?->isToday() && $participant->schedule?->start_time <= now()->format('H:i:s')))
                )) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'Jadwal kelas sudah dibatalkan atau selesai. Pembayaran tidak dapat dikonfirmasi.',
                    ]);
                }

                $participantStatus = match ($childStatus) {
                    'active' => 'confirmed',
                    'cancelled' => 'cancelled',
                    default => 'pending',
                };

                SessionParticipant::query()
                    ->whereKey($participants->pluck('id'))
                    ->update(['status' => $participantStatus]);
            }
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function show(Transaction $transaction): Response
    {
        $transaction->load(['sessionParticipants.schedule.sportClass.facility', 'sessionParticipants.schedule.trainer']);
        $details = [];
        $itemName = 'Transaksi';

        if ($transaction->transactionable_type === 'booking') {
            // Booking menyimpan ID-nya di kode BK-xxxx pada invoice transaksi.
            $bookingId = preg_match('/^BK-(\\d+)$/', (string) $transaction->invoice_number, $matches)
                ? (int) $matches[1]
                : null;

            $booking = $bookingId
                ? Booking::query()->with(['court.facility', 'trainerAddon.trainer'])->find($bookingId)
                : null;

            if ($booking) {
                $itemName = 'Booking Lapangan';
                $details = [
                    ['label' => 'Jenis pesanan', 'value' => 'Booking lapangan'],
                    ['label' => 'Fasilitas / olahraga', 'value' => $booking->court?->facility?->name ?? 'Belum ditentukan'],
                    ['label' => 'Lapangan', 'value' => $booking->court?->name ?? 'Belum ditentukan'],
                    ['label' => 'Tanggal bermain', 'value' => $booking->date?->translatedFormat('l, d F Y')],
                    ['label' => 'Jam bermain', 'value' => $booking->start_time && $booking->end_time
                        ? substr((string) $booking->start_time, 0, 5).' - '.substr((string) $booking->end_time, 0, 5)
                        : 'Menunggu penjadwalan'],
                    ['label' => 'Durasi', 'value' => $booking->start_time && $booking->end_time
                        ? (string) max(1, (int) round((strtotime((string) $booking->end_time) - strtotime((string) $booking->start_time)) / 3600)).' jam'
                        : '—'],
                    ['label' => 'Add-on trainer', 'value' => $booking->trainerAddon?->trainer?->name ?? 'Tidak ada'],
                    ['label' => 'Status booking', 'value' => ucfirst((string) $booking->status)],
                ];
            } else {
                $membership = UserMembership::query()
                    ->with('package.facility')
                    ->where('transaction_id', $transaction->id)
                    ->first();

                if ($membership) {
                    $itemName = 'Membership';
                    $details = [
                        ['label' => 'Jenis pesanan', 'value' => 'Membership'],
                        ['label' => 'Paket', 'value' => $membership->package?->name],
                        ['label' => 'Fasilitas / olahraga', 'value' => $membership->package?->facility?->name],
                        ['label' => 'Masa berlaku', 'value' => $membership->start_date && $membership->end_date
                            ? $membership->start_date->translatedFormat('d F Y').' - '.$membership->end_date->translatedFormat('d F Y')
                            : 'Menunggu aktivasi pembayaran'],
                        ['label' => 'Status membership', 'value' => ucfirst((string) $membership->status)],
                    ];
                }
            }
        } elseif ($transaction->transactionable_type === 'membership') {
            $membership = UserMembership::query()
                ->with('package.facility')
                ->where('transaction_id', $transaction->id)
                ->first();

            if ($membership) {
                $itemName = 'Membership';
                $details = [
                    ['label' => 'Jenis pesanan', 'value' => 'Membership'],
                    ['label' => 'Paket', 'value' => $membership->package?->name],
                    ['label' => 'Fasilitas / olahraga', 'value' => $membership->package?->facility?->name],
                    ['label' => 'Masa berlaku', 'value' => $membership->start_date && $membership->end_date
                        ? $membership->start_date->translatedFormat('d F Y').' - '.$membership->end_date->translatedFormat('d F Y')
                        : 'Menunggu aktivasi pembayaran'],
                    ['label' => 'Status membership', 'value' => ucfirst((string) $membership->status)],
                ];
            }
        } elseif ($transaction->transactionable_type === 'trainer_package') {
            $trainerPurchase = MemberTrainerPackage::query()
                ->with(['trainerPackage.facility', 'facility', 'trainer'])
                ->where('transaction_id', $transaction->id)
                ->first();

            if ($trainerPurchase) {
                $itemName = 'Paket Personal Trainer';
                $details = [
                    ['label' => 'Jenis pesanan', 'value' => 'Paket personal trainer'],
                    ['label' => 'Paket trainer', 'value' => $trainerPurchase->trainerPackage?->name],
                    ['label' => 'Fasilitas', 'value' => $trainerPurchase->facility?->name ?? $trainerPurchase->trainerPackage?->facility?->name],
                    ['label' => 'Trainer', 'value' => $trainerPurchase->trainer?->name ?? 'Belum ditentukan'],
                    ['label' => 'Jumlah sesi', 'value' => (string) $trainerPurchase->total_sessions],
                    ['label' => 'Sesi terpakai', 'value' => (string) $trainerPurchase->used_sessions],
                    ['label' => 'Sisa sesi', 'value' => (string) $trainerPurchase->remaining_sessions],
                    ['label' => 'Masa berlaku', 'value' => $trainerPurchase->start_date && $trainerPurchase->expires_at
                        ? $trainerPurchase->start_date->translatedFormat('d F Y').' - '.$trainerPurchase->expires_at->translatedFormat('d F Y')
                        : 'Menunggu aktivasi pembayaran'],
                    ['label' => 'Status paket', 'value' => ucfirst((string) $trainerPurchase->status)],
                ];
            }
        } elseif ($transaction->transactionable_type === 'class') {
            $classParticipant = $transaction->sessionParticipants->first();
            $classSchedule = $classParticipant?->schedule;

            if ($classSchedule) {
                $itemName = 'Pendaftaran Kelas';
                $details = [
                    ['label' => 'Jenis pesanan', 'value' => 'Pendaftaran kelas'],
                    ['label' => 'Kelas', 'value' => $classSchedule->sportClass?->name],
                    ['label' => 'Fasilitas', 'value' => $classSchedule->sportClass?->facility?->name],
                    ['label' => 'Trainer', 'value' => $classSchedule->trainer?->name ?? 'Belum ditentukan'],
                    ['label' => 'Tanggal kelas', 'value' => $classParticipant?->session_date?->translatedFormat('l, d F Y')],
                    ['label' => 'Waktu kelas', 'value' => substr((string) $classSchedule->start_time, 0, 5).' - '.substr((string) $classSchedule->end_time, 0, 5)],
                    ['label' => 'Peserta', 'value' => $transaction->sessionParticipants->map(fn (SessionParticipant $participant) => $participant->guest_name ?: 'Umum')->implode(', ')],
                    ['label' => 'Jumlah peserta', 'value' => (string) $transaction->sessionParticipants->count()],
                    ['label' => 'Status peserta', 'value' => ucfirst((string) $classParticipant->status)],
                ];
            }
        } elseif ($transaction->transactionable_type === 'entry') {
            $itemName = 'Tiket Masuk';
            $details = [
                ['label' => 'Jenis pesanan', 'value' => 'Tiket masuk per orang'],
                ['label' => 'Pelanggan', 'value' => $transaction->customer_name ?: 'Umum'],
                ['label' => 'Total pembayaran', 'value' => 'Rp '.number_format((float) $transaction->amount, 0, ',', '.')],
            ];
        }

        if ($details === []) {
            $details = [
                ['label' => 'Jenis transaksi', 'value' => match ($transaction->transactionable_type) {
                    'booking' => 'Booking / pesanan layanan',
                    'membership' => 'Membership',
                    'trainer_package' => 'Paket personal trainer',
                    'class' => 'Pendaftaran kelas',
                    'entry' => 'Tiket masuk',
                    default => ucfirst(str_replace('_', ' ', (string) $transaction->transactionable_type)),
                }],
                ['label' => 'Informasi pesanan', 'value' => 'Data item pesanan tidak ditemukan untuk transaksi ini.'],
            ];
        }

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
                'item_name' => $itemName,
                'details' => $details,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['nullable', 'required_without:class_schedule_id', 'integer', 'exists:packages,id'],
            'class_schedule_id' => ['nullable', 'required_without:package_id', 'integer', 'exists:class_schedules,id'],
            'participants' => ['nullable', 'array', 'min:1'],
            'participants.*' => ['required', 'string', 'max:255'],
            'pricing_rule_id' => ['nullable', 'integer', 'exists:package_pricing_rules,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'payment_status' => ['required', Rule::in(['paid', 'pending'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'court_id' => ['nullable', 'integer', 'exists:courts,id'],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (! empty($data['class_schedule_id'])) {
            $this->storeClassSession($data);
            return redirect()->route('transactions.index')->with('success', 'Pendaftaran kelas berhasil disimpan.');
        }

        $package = Package::with('facility')->findOrFail($data['package_id']);
        if ($package->type === 'session') {
            abort_unless($package->facility?->has_booking, 422, 'Booking untuk olahraga ini sedang dinonaktifkan.');
            $this->storeBooking($package, $data);
            return redirect()->route('transactions.index')->with('success', 'Booking dan transaksi pembayaran berhasil disimpan.');
        }

        if ($package->type === 'membership') {
            abort_unless($package->facility?->has_membership, 422, 'Membership untuk olahraga ini sedang dinonaktifkan.');
            $data['amount'] = (float) $package->price;
            $this->storeMembership($package, $data);
        } else {
            abort_unless($package->facility?->has_session, 422, 'Tiket masuk untuk olahraga ini sedang dinonaktifkan.');
            $rule = ! empty($data['pricing_rule_id'])
                ? PackagePricingRule::query()->where('package_id', $package->id)->find($data['pricing_rule_id'])
                : null;
            if (! $rule) {
                throw ValidationException::withMessages(['pricing_rule_id' => 'Pilih aturan harga yang sesuai dengan paket.']);
            }
            // Satu transaksi kasir untuk tiket masuk selalu satu item.
            $amount = (float) $rule->price;
            Transaction::create([
                'user_id' => null,
                'transactionable_type' => 'entry',
                'customer_name' => $data['customer_name'] ?: 'Umum',
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'] === 'paid' ? 'fully_paid' : 'unpaid',
            ]);
        }

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil disimpan.');
    }

    /** @param array<string, mixed> $data */
    private function storeClassSession(array $data): void
    {
        $names = collect($data['participants'] ?? [])->map(fn ($name) => trim((string) $name))->filter()->values();
        if ($names->isEmpty()) {
            throw ValidationException::withMessages(['participants' => 'Isi minimal satu peserta.']);
        }

        DB::transaction(function () use ($data, $names) {
            $schedule = ClassSchedule::query()->with(['sportClass.facility'])->lockForUpdate()->findOrFail($data['class_schedule_id']);
            abort_unless($schedule->is_active && $schedule->sportClass?->is_active && $schedule->sportClass?->facility?->status === 'active' && $schedule->sportClass?->facility?->has_session, 422, 'Jadwal kelas tidak tersedia.');
            if (empty($data['date'])) {
                throw ValidationException::withMessages(['date' => 'Tanggal kelas wajib dipilih.']);
            }
            $date = Carbon::parse($data['date'])->startOfDay();
            abort_unless($date->gte(today()) && strtolower($date->englishDayOfWeek) === $schedule->day, 422, 'Tanggal tidak sesuai hari jadwal kelas.');
            abort_if($date->isToday() && $schedule->start_time <= now()->format('H:i:s'), 422, 'Jadwal kelas sudah dimulai atau terlewat.');
            $capacity = (int) ($schedule->capacity ?? $schedule->sportClass->default_capacity ?? 1);
            $registered = SessionParticipant::query()->where('class_schedule_id', $schedule->id)->whereDate('session_date', $date->toDateString())->whereIn('status', ['pending', 'confirmed'])->lockForUpdate()->count();
            if ($registered + $names->count() > $capacity) {
                throw ValidationException::withMessages(['participants' => 'Jumlah peserta melebihi sisa kuota kelas.']);
            }
            $price = (float) $schedule->price;
            $paid = ($data['payment_status'] ?? 'paid') === 'paid';
            $transaction = Transaction::create([
                'transactionable_type' => 'class',
                'customer_name' => $data['customer_name'] ?? $names->first(),
                'amount' => $price * $names->count(),
                'payment_method' => $data['payment_method'],
                'payment_status' => $paid ? 'fully_paid' : 'unpaid',
            ]);
            foreach ($names as $name) {
                SessionParticipant::create([
                    'class_schedule_id' => $schedule->id,
                    'session_date' => $date->toDateString(),
                    'guest_name' => $name === 'Umum' ? null : $name,
                    'transaction_id' => $transaction->id,
                    'price' => $price,
                    'status' => $paid ? 'confirmed' : 'pending',
                ]);
            }
        });
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
        abort_unless($package->facility?->has_booking, 422, 'Booking untuk olahraga ini sedang dinonaktifkan.');
        $turnaround = max(0, (int) $package->facility->turnaround_minutes);
        $date = Carbon::parse($data['date']);
        $startMinutes = $this->toMinutes($data['start_time']);
        $durationMinutes = max(1, (int) $package->duration_minutes);
        $endMinutes = $startMinutes + $durationMinutes;
        $startTime = $this->toTime($startMinutes);
        $endTime = $this->toTime($endMinutes);

        $windows = BookingTimeSlot::query()
            ->where('facility_id', $package->facility_id)
            ->where('day', strtolower($date->englishDayOfWeek))
            ->where('is_active', true)
            ->get(['start_time', 'end_time', 'price']);
        $step = $durationMinutes + $turnaround;
        $validSlot = $windows->contains(function (BookingTimeSlot $window) use ($startMinutes, $endMinutes, $step) {
            $windowStart = $this->toMinutes((string) $window->start_time);
            $windowEnd = $this->toMinutes((string) $window->end_time);

            return $startMinutes >= $windowStart
                && ($startMinutes - $windowStart) % $step === 0
                && $endMinutes <= $windowEnd;
        });

        if (! $validSlot) {
            throw ValidationException::withMessages([
                'start_time' => 'Jam yang dipilih tidak sesuai Master Jam Booking. Silakan pilih slot yang tersedia.',
            ]);
        }

        if ($date->isToday() && $startMinutes < (now()->hour * 60 + now()->minute)) {
            throw ValidationException::withMessages([
                'start_time' => 'Jam yang dipilih sudah lewat.',
            ]);
        }

        DB::transaction(function () use ($package, $data, $date, $startTime, $endTime, $startMinutes, $endMinutes, $turnaround, $durationMinutes) {
            $court = \App\Models\Court::query()
                ->lockForUpdate()
                ->whereKey($data['court_id'])
                ->where('facility_id', $package->facility_id)
                ->where('status', 'available')
                ->firstOrFail();

            $conflict = Booking::query()
                ->where('court_id', $court->id)
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
                throw ValidationException::withMessages(['start_time' => 'Lapangan sudah terisi pada jam tersebut. Pilih jam atau lapangan lain.']);
            }

            $window = BookingTimeSlot::query()
                ->where('facility_id', $package->facility_id)
                ->where('day', strtolower($date->englishDayOfWeek))
                ->where('is_active', true)
                ->whereTime('start_time', '<=', $startTime)
                ->whereTime('end_time', '>=', $endTime)
                ->first();

            if (! $window) {
                throw ValidationException::withMessages(['start_time' => 'Jam booking tidak sesuai master jam operasional.']);
            }

            $amount = ((float) $window->price * ($durationMinutes / 60)) + (float) $court->price_adjustment;
            $paid = ($data['payment_status'] ?? 'paid') === 'paid';
            $booking = Booking::create([
                'guest_name' => $data['customer_name'] ?? 'Umum',
                'court_id' => $court->id,
                'device_id' => Device::forCourt($court->id)?->id,
                'date' => $date->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $paid ? 'confirmed' : 'pending',
            ]);

            Transaction::create([
                'transactionable_type' => 'booking',
                'guest_name' => $data['customer_name'] ?: 'Umum',
                'amount' => $amount,
                'invoice_number' => 'BK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                'booking_status' => $paid ? 'confirmed' : 'pending',
                'payment_method' => $data['payment_method'],
                'payment_status' => $paid ? 'fully_paid' : 'unpaid',
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
