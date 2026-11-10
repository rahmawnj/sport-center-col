<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingTimeSlot;
use App\Models\Court;
use App\Models\Device;
use App\Models\Facility;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\UserMembership;
use App\Services\PackagePricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    public function __construct(private PackagePricingService $pricing) {}

    private const DEFAULT_OPEN = '08:00';

    private const DEFAULT_CLOSE = '23:00';

    public function index(Request $request): Response
    {
        $facilities = Facility::query()
            ->where('status', 'active')
            ->where('is_bookable_online', true)
            ->with(['packages' => fn ($query) => $query->where('type', 'session')->orderBy('name')->with(['pricingRules', 'trainerAddons.facilities'])])
            ->orderBy('name')
            ->get()
            ->map(fn (Facility $facility) => [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
                'packages' => $facility->packages->map(fn (Package $package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'type' => $package->type,
                    'duration_minutes' => $package->duration_minutes,
                    'trainer_addons' => $package->trainerAddons
                        ->filter(fn ($trainer) => $trainer->facilities->contains('id', $facility->id))
                        ->map(fn ($trainer) => [
                        'id' => $trainer->id,
                        'name' => $trainer->name,
                        'specialty' => $trainer->specialty,
                        'price' => (float) $trainer->pivot->price,
                    ])->values(),
                    'pricing_summary' => $this->pricing->summary($package, Carbon::today()),
                    'pricing_rules' => $this->pricing->rulesForDate($package, Carbon::today())
                        ->map(fn ($rule) => [
                            'day_type' => $rule->day_type,
                            'start_time' => $rule->start_time ? substr((string) $rule->start_time, 0, 5) : null,
                            'end_time' => $rule->end_time ? substr((string) $rule->end_time, 0, 5) : null,
                            'price' => (float) $rule->price,
                        ])->values(),
                ])->values(),
            ]);

        $requestedFacility = (string) $request->query('facility', '');
        $initialFacility = $facilities->first(
            fn ($facility) => $facility['slug'] === $requestedFacility
                || (string) $facility['id'] === $requestedFacility
        );

        return Inertia::render('book/Index', [
            'facilities' => $facilities,
            'currentUser' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ] : null,
            'initialFacilityId' => $initialFacility['id'] ?? null,
            'success' => $request->session()->get('success'),
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $package = Package::with(['facility.courts', 'pricingRules'])->findOrFail($data['package_id']);
        $date = Carbon::parse($data['date']);

        $bookings = Booking::with(['court:id,name', 'user:id,name'])
            ->whereIn('court_id', $package->facility->courts->pluck('id'))
            ->whereDate('date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get()
            ->map(fn (Booking $booking) => [
                'court_id' => $booking->court_id,
                'court_name' => $booking->court?->name,
                'start_time' => substr((string) $booking->start_time, 0, 5),
                'end_time' => substr((string) $booking->end_time, 0, 5),
                'status' => $booking->status,
                'customer_name' => $booking->user?->name ?? $booking->guest_name,
            ])->values();

        $courts = Court::query()
            ->where('facility_id', $package->facility_id)
            ->where('status', 'available')
            ->orderBy('name')
            ->get()
            ->map(fn ($court) => [
                'id' => $court->id,
                'name' => $court->name,
                'amenities' => $court->amenities ?? [],
                'price_adjustment' => (float) $court->price_adjustment,
                'status' => $court->status,
            ])->values();

        return response()->json([
            'slots' => $this->buildSlots($package, $date),
            'bookings' => $bookings,
            'courts' => $courts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer'],
        ]);

        $package = Package::with(['pricingRules', 'facility', 'trainerAddons'])->findOrFail($data['package_id']);

        if ($package->type === 'membership') {
            abort_unless($request->user(), 403, 'Silakan login sebagai member untuk membeli membership.');
            abort_unless($request->user()->role?->name === 'Member', 403, 'Pembelian membership hanya tersedia untuk akun Member.');

            $data = array_merge($data, $request->validate([
                'terms_accepted' => ['required', 'accepted'],
            ]));

            $this->storeDirect($request, $package, $data);

            return redirect()->route('member.membership')->with('success', 'Pembelian membership tersimpan di akunmu. Ikuti status pembayaran pada menu Pembayaran.');
        }

        $contactRules = $request->user()
            ? [
                'guest_name' => ['nullable', 'string', 'max:255'],
                'guest_email' => ['nullable', 'email', 'max:255'],
                'guest_phone' => ['nullable', 'string', 'max:50'],
            ]
            : [
                'guest_name' => ['required', 'string', 'max:255'],
                'guest_email' => ['required', 'email', 'max:255'],
                'guest_phone' => ['required', 'string', 'max:50'],
            ];

        $data = array_merge($data, $request->validate(array_merge([
            'court_id' => ['nullable', 'integer', 'exists:courts,id'],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:16'],
            'terms_accepted' => ['required', 'accepted'],
        ], $contactRules)));

        if ($package->type !== 'session') {
            $this->storeDirect($request, $package, $data);

            return redirect()->route('booking.index')->with('success', 'Pesanan berhasil dibuat dan tersimpan.');
        }

        $trainerAddon = null;
        if (! empty($data['trainer_id'])) {
            $trainerAddon = $package->trainerAddons->firstWhere('id', (int) $data['trainer_id']);
            if (! $trainerAddon || ! $trainerAddon->facilities()->whereKey($package->facility_id)->exists()) {
                throw ValidationException::withMessages(['trainer_id' => 'Trainer tersebut tidak tersedia sebagai add-on untuk paket ini.']);
            }
        }

        $addonPrice = $trainerAddon ? (float) $trainerAddon->pivot->price : 0;
        $total = (int) round($this->bookingPrice($package, $data) + $addonPrice);

        $booking = DB::transaction(function () use ($request, $package, $data, $total, $trainerAddon, $addonPrice) {
            if ($trainerAddon) {
                // Serialize bookings for the same trainer to prevent overlapping assignments.
                $trainerAddon->newQuery()->whereKey($trainerAddon->id)->lockForUpdate()->first();
                $trainerBookings = BookingTrainerAddon::query()
                    ->where('trainer_id', $trainerAddon->id)
                    ->whereHas('booking', fn ($query) => $query
                        ->whereDate('date', $data['date'])
                        ->whereNotIn('status', ['cancelled']));

                if (! empty($data['start_time'])) {
                    $trainerStart = Carbon::createFromFormat('H:i', $data['start_time']);
                    $trainerEnd = $trainerStart->copy()->addHours(max(1, (int) ($data['duration_hours'] ?? 1)));
                    $trainerBookings->whereHas('booking', fn ($query) => $query
                        ->whereTime('start_time', '<', $trainerEnd->format('H:i:s'))
                        ->whereTime('end_time', '>', $trainerStart->format('H:i:s')));
                }

                if ($trainerBookings->exists()) {
                    throw ValidationException::withMessages([
                        'trainer_id' => 'Trainer sudah memiliki booking pada tanggal atau jam tersebut. Silakan pilih trainer lain.',
                    ]);
                }
            }

            $booking = $this->storeBooking($request, $package, $data);
            if ($trainerAddon) {
                $booking->trainerAddon()->create([
                    'package_id' => $package->id,
                    'trainer_id' => $trainerAddon->id,
                    'price' => $addonPrice,
                ]);
            }
            $user = $request->user();

            // Keep the receipt code identical to the QR ticket code in transactions.
            $bookingCode = 'BK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);

            Transaction::query()->create([
                'booking_code' => $bookingCode,
                'transactionable_type' => 'booking',
                'user_id' => $user?->id,
                'guest_name' => $user ? $user->name : $data['guest_name'],
                'total_amount' => $total,
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'payment_status' => 'unpaid',
                'booking_status' => 'pending',
            ]);

            return $booking;
        });

        Cache::put('booking_receipt_'.$booking->id, [
            'package' => $package->name,
            'trainer' => $trainerAddon?->name,
            'trainer_addon_price' => $addonPrice,
            'total' => $total,
        ], now()->addDays(30));

        return redirect()->route('booking.receipt', ['booking' => $booking->id]);
    }

    public function receipt(Request $request, Booking $booking): Response
    {
        $booking->load(['court.facility', 'user', 'trainerAddon.trainer']);
        $receiptDetails = Cache::get('booking_receipt_'.$booking->id, []);

        return Inertia::render('book/Receipt', [
            'receiptUrl' => route('booking.receipt', ['booking' => $booking->id]),
            'receipt' => [
                'code' => 'BK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                'customerName' => $booking->user?->name ?? $booking->guest_name,
                'customerEmail' => $booking->guest_email,
                'customerPhone' => $booking->guest_phone,
                'facility' => $booking->court?->facility?->name ?? 'Olahraga',
                'court' => $booking->court?->name ?? '—',
                'package' => (string) ($receiptDetails['package'] ?? 'Booking sesi'),
                'trainer' => $booking->trainerAddon?->trainer?->name,
                'trainerAddonPrice' => (int) ($booking->trainerAddon?->price ?? 0),
                'date' => $booking->date?->format('d M Y'),
                'startTime' => substr((string) $booking->start_time, 0, 5),
                'endTime' => substr((string) $booking->end_time, 0, 5),
                'status' => (string) $booking->status,
                'total' => (int) ($receiptDetails['total'] ?? 0),
            ],
        ]);
    }

    /**
     * Calculate the server-side total for the selected continuous booking hours.
     *
     * @param  array<string, mixed>  $data
     */
    private function bookingPrice(Package $package, array $data): float
    {
        $date = Carbon::parse($data['date']);
        $start = $this->toMinutes($data['start_time'] ?? '00:00');
        $duration = max(1, (int) ($data['duration_hours'] ?? 1)) * 60;
        $end = $start + $duration;
        $window = BookingTimeSlot::query()
            ->where('facility_id', $package->facility_id)
            ->where('day', strtolower($date->englishDayOfWeek))
            ->where('is_active', true)
            ->get()
            ->first(fn (BookingTimeSlot $slot) => $start >= $this->toMinutes((string) $slot->start_time)
                && $end <= $this->toMinutes((string) $slot->end_time));

        if (! $window) {
            throw ValidationException::withMessages(['start_time' => 'Jadwal berada di luar jam operasional atau belum memiliki harga.']);
        }

        return ((float) $window->price * ($duration / 60)) + $this->courtAdjustment($data['court_id'] ?? null);
    }

    private function courtAdjustment(mixed $courtId): float
    {
        return $courtId
            ? (float) Court::query()->whereKey($courtId)->value('price_adjustment')
            : 0.0;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeBooking(Request $request, Package $package, array $data): Booking
    {
        if (empty($data['date'])) {
            throw ValidationException::withMessages(['date' => 'Tanggal sesi wajib dipilih.']);
        }

        $date = Carbon::parse($data['date']);
        $hasOperatingHours = BookingTimeSlot::query()
            ->where('facility_id', $package->facility_id)
            ->where('day', strtolower($date->englishDayOfWeek))
            ->where('is_active', true)
            ->exists();

        if (! $hasOperatingHours) {
            $user = $request->user();

            return Booking::create([
                'user_id' => $user?->id,
                'guest_name' => $user ? $user->name : $data['guest_name'],
                'guest_email' => $user ? $user->email : $data['guest_email'],
                'guest_phone' => $user ? null : $data['guest_phone'],
                'court_id' => null,
                    'date' => $date->toDateString(),
                'start_time' => null,
                'end_time' => null,
                'status' => 'pending',
            ]);
        }

        if (empty($data['court_id']) || empty($data['start_time'])) {
            throw ValidationException::withMessages(['start_time' => 'Pilih jam dan lapangan yang tersedia.']);
        }
        $courtIsBookable = $package->facility->courts()
            ->whereKey($data['court_id'])
            ->where('status', 'available')
            ->exists();

        if (! $courtIsBookable) {
            throw ValidationException::withMessages([
                'court_id' => 'Lapangan yang dipilih tidak tersedia untuk olahraga ini. Silakan pilih lapangan lain.',
            ]);
        }

        $startMinutes = $this->toMinutes($data['start_time']);
        $durationHours = max(1, (int) ($data['duration_hours'] ?? 1));
        $durationMinutes = $durationHours * 60;
        $endMinutes = $startMinutes + $durationMinutes;
        $turnaround = max(0, (int) $package->facility->turnaround_minutes);

        if (! $this->isMasterSlotStart($package->facility_id, $date, $startMinutes, $durationMinutes)) {
            throw ValidationException::withMessages(['start_time' => 'Jam yang dipilih tidak sesuai Master Jam Booking. Silakan pilih slot yang tersedia.']);
        }

        if ($date->isToday() && $startMinutes < (now()->hour * 60 + now()->minute)) {
            throw ValidationException::withMessages(['start_time' => 'Jam yang dipilih sudah lewat.']);
        }

        return DB::transaction(function () use ($request, $data, $date, $startMinutes, $endMinutes, $turnaround) {
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
                throw ValidationException::withMessages(['start_time' => 'Jadwal tersebut baru saja dibooking. Silakan pilih jam lain.']);
            }

            $user = $request->user();

            return Booking::create([
                'user_id' => $user?->id,
                'guest_name' => $user ? $user->name : $data['guest_name'],
                'guest_email' => $user ? $user->email : $data['guest_email'],
                'guest_phone' => $user ? null : $data['guest_phone'],
                'court_id' => $data['court_id'],
                'device_id' => Device::forCourt($data['court_id'])?->id,
                'date' => $date->toDateString(),
                'start_time' => $this->toTime($startMinutes),
                'end_time' => $this->toTime($endMinutes),
                'status' => 'pending',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeDirect(Request $request, Package $package, array $data): void
    {
        $price = $this->basePrice($package, Carbon::today());

        if ($price === null) {
            throw ValidationException::withMessages(['package_id' => 'Paket ini belum memiliki harga.']);
        }

        $user = $request->user();

        if ($package->type === 'membership') {
            if (! $user) {
                throw ValidationException::withMessages(['package_id' => 'Membership memerlukan akun. Silakan masuk terlebih dahulu.']);
            }

            $start = Carbon::today();
            $end = $start->copy()->addDays(max(1, (int) ($package->duration_days ?? 30)));

            DB::transaction(function () use ($user, $package, $start, $end, $price, $data) {
                $membership = UserMembership::create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => 'active',
                ]);

                Transaction::query()->create([
                    'user_id' => $user->id,
                    'transactionable_type' => 'membership',
                    'total_amount' => $price,
                    'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                    'payment_status' => 'unpaid',
                ]);
            });

            return;
        }

        Transaction::query()->create([
            'user_id' => $user?->id,
            'device_id' => Device::forFacility($package->facility_id)?->id,
            'transactionable_type' => 'booking',
            'guest_name' => $user ? $user->name : $data['guest_name'],
            'amount' => $price,
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'payment_status' => 'unpaid',
        ]);
    }

    private function basePrice(Package $package, Carbon $date): ?float
    {
        return $this->pricing->summary($package, $date)['starting_price'];
    }

    /**
     * @return array<int, array{start_time: string, end_time: string, price: float, courts: array<int, array{id: int, name: string}>}>
     */
    private function buildSlots(Package $package, Carbon $date): array
    {
        $duration = max(1, (int) $package->duration_minutes);
        $turnaround = max(0, (int) $package->facility->turnaround_minutes);
        $step = $duration + $turnaround;
        $day = strtolower($date->englishDayOfWeek);

        $courts = Court::query()
            ->where('facility_id', $package->facility_id)
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        $bookings = Booking::query()
            ->whereIn('court_id', $courts->pluck('id'))
            ->whereDate('date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->get(['court_id', 'start_time', 'end_time']);

        $windows = BookingTimeSlot::query()
            ->where('facility_id', $package->facility_id)
            ->where('day', $day)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        $slots = [];
        foreach ($windows as $window) {
            $cursor = $this->toMinutes((string) $window->start_time);
            $windowEnd = $this->toMinutes((string) $window->end_time);

            while ($cursor + $duration <= $windowEnd) {
                if ($date->isToday() && $cursor < (now()->hour * 60 + now()->minute)) {
                    $cursor += $step;
                    continue;
                }

                $slotEnd = $cursor + $duration;
                $available = $courts
                    ->reject(fn ($court) => $bookings->contains(
                        fn ($booking) => (int) $booking->court_id === $court->id
                            && $this->windowsOverlap(
                                $cursor,
                                $slotEnd,
                                $this->toMinutes((string) $booking->start_time),
                                $this->toMinutes((string) $booking->end_time),
                                $turnaround,
                            )
                    ))
                    ->map(fn ($court) => [
                        'id' => $court->id,
                        'name' => $court->name,
                        'amenities' => $court->amenities ?? [],
                        'price_adjustment' => (float) $court->price_adjustment,
                    ])
                    ->values();

                $slots[] = [
                    'start_time' => $this->toTime($cursor),
                    'end_time' => $this->toTime($slotEnd),
                    'price' => (float) $window->price * ($duration / 60),
                    'courts' => $available,
                ];

                $cursor += $step;
            }
        }

        return $slots;
    }

    private function isMasterSlotStart(int $facilityId, Carbon $date, int $startMinutes, int $durationMinutes): bool
    {
        $day = strtolower($date->englishDayOfWeek);
        $step = max(1, $durationMinutes) + $turnaround;

        return BookingTimeSlot::query()
            ->where('facility_id', $facilityId)
            ->where('day', $day)
            ->where('is_active', true)
            ->get(['start_time', 'end_time'])
            ->contains(function (BookingTimeSlot $window) use ($startMinutes, $durationMinutes, $step) {
                $windowStart = $this->toMinutes((string) $window->start_time);
                $windowEnd = $this->toMinutes((string) $window->end_time);

                return $startMinutes >= $windowStart
                    && ($startMinutes - $windowStart) % $step === 0
                    && $startMinutes + $durationMinutes <= $windowEnd;
            });
    }

    /**
     * Two bookings clash when their occupied windows overlap. Each booking occupies
     * [start, end] for play plus `turnaround` minutes afterwards for preparation.
     */
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
}
