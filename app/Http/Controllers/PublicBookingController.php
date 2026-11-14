<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingTimeSlot;
use App\Models\Court;
use App\Models\Device;
use App\Models\Facility;
use App\Models\Package;
use App\Models\Transaction;
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
    private const DEFAULT_OPEN = '08:00';

    private const DEFAULT_CLOSE = '23:00';

    public function index(Request $request): Response
    {
        $facilities = Facility::query()
            ->where('status', 'active')
            ->where('has_booking', true)
            ->where('is_bookable_online', true)
            ->with(['courts' => fn ($query) => $query->where('status', 'available')->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn (Facility $facility) => [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
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
            'facility_id' => ['nullable', 'required_without:package_id', 'integer', 'exists:facilities,id'],
            'package_id' => ['nullable', 'required_without:facility_id', 'integer', 'exists:packages,id'],
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(7)->toDateString()],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:16'],
        ]);

        // Availability can be requested by facility and duration without a package.
        $package = ! empty($data['package_id'])
            ? Package::with('facility')->findOrFail($data['package_id'])
            : null;
        $facility = Facility::with('courts')->findOrFail($data['facility_id'] ?? $package?->facility_id);
        abort_unless($facility->status === 'active' && $facility->has_booking, 422, 'Booking untuk olahraga ini sedang dinonaktifkan.');
        $slotDuration = $package
            ? max(1, (int) $package->duration_minutes)
            : max(1, (int) ($data['duration_hours'] ?? 1)) * 60;
        $date = Carbon::parse($data['date']);

        $bookings = Booking::with(['court:id,name', 'user:id,name'])
            ->whereIn('court_id', $facility->courts->pluck('id'))
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

        $courts = $facility->courts
            ->where('status', 'available')
            ->map(fn ($court) => [
                'id' => $court->id,
                'name' => $court->name,
                'amenities' => $court->amenities ?? [],
                'price_adjustment' => (float) $court->price_adjustment,
                'status' => $court->status,
            ])->values();

        return response()->json([
            'slots' => $this->buildSlots($facility, $date, $slotDuration),
            'bookings' => $bookings,
            'courts' => $courts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $contactRules = [
            'guest_name' => $request->user()
                ? ['nullable', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'guest_email' => $request->user()
                ? ['nullable', 'email', 'max:255']
                : ['required', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:50'],
        ];

        $data = $request->validate(array_merge([
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(7)->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:16'],
            'selected_slots' => ['required', 'array', 'min:1', 'max:16'],
            'selected_slots.*.start_time' => ['required', 'date_format:H:i'],
            'selected_slots.*.end_time' => ['required', 'date_format:H:i'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer'],
            'terms_accepted' => ['required', 'accepted'],
        ], $contactRules));

        $facility = Facility::query()->findOrFail($data['facility_id']);
        abort_unless($facility->status === 'active' && $facility->has_booking && $facility->is_bookable_online, 422, 'Olahraga ini tidak tersedia untuk booking online.');

        $total = (int) round($this->bookingPrice($facility, $data));

        $booking = DB::transaction(function () use ($request, $facility, $data, $total) {
            $booking = $this->storeBooking($request, $facility, $data);
            $user = $request->user();
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
    private function bookingPrice(Facility $facility, array $data): float
    {
        $date = Carbon::parse($data['date']);
        $selectedSlots = collect($data['selected_slots'] ?? [])->sortBy('start_time')->values();
        $turnaround = max(0, (int) $facility->turnaround_minutes);
        $windows = BookingTimeSlot::query()
            ->where('facility_id', $facility->id)
            ->where('day', strtolower($date->englishDayOfWeek))
            ->where('is_active', true)
            ->get();

        $total = 0.0;
        $previousEnd = null;
        $step = 60 + $turnaround;
        foreach ($selectedSlots as $selected) {
            $slotStart = $this->toMinutes($selected['start_time']);
            $slotEnd = $this->toMinutes($selected['end_time']);
            if ($slotEnd - $slotStart !== 60 || ($previousEnd !== null && $slotStart !== $previousEnd + $turnaround)) {
                throw ValidationException::withMessages(['selected_slots' => 'Pilih jam secara berurutan seperti booking kasir.']);
            }
            $window = $windows->first(function (BookingTimeSlot $window) use ($slotStart, $slotEnd, $step) {
                $windowStart = $this->toMinutes((string) $window->start_time);
                $windowEnd = $this->toMinutes((string) $window->end_time);
                return $slotStart >= $windowStart && $slotEnd <= $windowEnd
                    && ($slotStart - $windowStart) % $step === 0;
            });
            if (! $window) {
                throw ValidationException::withMessages(['selected_slots' => 'Ada jam yang tidak sesuai Master Jam Booking.']);
            }
            $total += (float) $window->price;
            $previousEnd = $slotEnd;
        }

        return $total + $this->courtAdjustment($data['court_id'] ?? null);
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
    private function storeBooking(Request $request, Facility $facility, array $data): Booking
    {
        $date = Carbon::parse($data['date']);
        $selectedSlots = collect($data['selected_slots'] ?? [])->sortBy('start_time')->values();
        $startMinutes = $this->toMinutes($selectedSlots->first()['start_time'] ?? $data['start_time']);
        $endMinutes = $this->toMinutes($selectedSlots->last()['end_time'] ?? $data['start_time']);
        $durationMinutes = $endMinutes - $startMinutes;
        $turnaround = max(0, (int) $facility->turnaround_minutes);

        $courtIsBookable = $facility->courts()
            ->whereKey($data['court_id'])
            ->where('status', 'available')
            ->exists();

        if (! $courtIsBookable) {
            throw ValidationException::withMessages([
                'court_id' => 'Lapangan yang dipilih tidak tersedia untuk olahraga ini. Silakan pilih lapangan lain.',
            ]);
        }

        if ($selectedSlots->isEmpty() || $selectedSlots->count() !== (int) $data['duration_hours']) {
            throw ValidationException::withMessages(['selected_slots' => 'Pilih minimal satu jam booking.']);
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
                'guest_phone' => $data['guest_phone'],
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
     * @return array<int, array{start_time: string, end_time: string, price: float, courts: array<int, array{id: int, name: string}>}>
     */
    private function buildSlots(Facility $facility, Carbon $date, int $duration = 60): array
    {
        $turnaround = max(0, (int) $facility->turnaround_minutes);
        $day = strtolower($date->englishDayOfWeek);

        $courts = $facility->courts()
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        $bookings = Booking::query()
            ->whereIn('court_id', $courts->pluck('id'))
            ->whereDate('date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->get(['court_id', 'start_time', 'end_time']);

        $windows = BookingTimeSlot::query()
            ->where('facility_id', $facility->id)
            ->where('day', $day)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        $slots = [];
        foreach ($windows as $window) {
            $windowStart = $this->toMinutes((string) $window->start_time);
            $windowEnd = $this->toMinutes((string) $window->end_time);
            $step = $duration > 0 ? $duration + $turnaround : 0;
            $starts = $duration > 0
                ? range($windowStart, max($windowStart, $windowEnd - $duration), max(1, $step))
                : [$windowStart];

            foreach ($starts as $cursor) {
                $slotEnd = $duration > 0 ? $cursor + $duration : $windowEnd;
                if ($slotEnd > $windowEnd || ($date->isToday() && $cursor < (now()->hour * 60 + now()->minute))) {
                    continue;
                }

                $courtOptions = $courts
                    ->map(function ($court) use ($bookings, $cursor, $slotEnd, $turnaround) {
                        $isAvailable = ! $bookings->contains(
                            fn ($booking) => (int) $booking->court_id === $court->id
                                && $this->windowsOverlap(
                                    $cursor,
                                    $slotEnd,
                                    $this->toMinutes((string) $booking->start_time),
                                    $this->toMinutes((string) $booking->end_time),
                                    $turnaround,
                                )
                        );

                        return [
                            'id' => $court->id,
                            'name' => $court->name,
                            'amenities' => $court->amenities ?? [],
                            'price_adjustment' => (float) $court->price_adjustment,
                            'is_available' => $isAvailable,
                        ];
                    })
                    ->values();

                $slots[] = [
                    'window_id' => $window->id,
                    'start_time' => $this->toTime($cursor),
                    'end_time' => $this->toTime($slotEnd),
                    'price' => (float) $window->price * (($slotEnd - $cursor) / 60),
                    'courts' => $courtOptions,
                ];
            }
        }

        return $slots;
    }

    private function isMasterSlotStart(int $facilityId, Carbon $date, int $startMinutes, int $durationMinutes): bool
    {
        $day = strtolower($date->englishDayOfWeek);
        $turnaround = max(0, (int) Facility::query()->whereKey($facilityId)->value('turnaround_minutes'));
        $step = 60 + $turnaround;
        $windows = BookingTimeSlot::query()
            ->where('facility_id', $facilityId)
            ->where('day', $day)
            ->where('is_active', true)
            ->get();

        $startWindow = $windows->first(function (BookingTimeSlot $window) use ($startMinutes, $step) {
            $windowStart = $this->toMinutes((string) $window->start_time);
            $windowEnd = $this->toMinutes((string) $window->end_time);

            return $startMinutes >= $windowStart
                && $startMinutes < $windowEnd
                && ($startMinutes - $windowStart) % $step === 0;
        });

        if (! $startWindow) {
            return false;
        }

        $endMinutes = $startMinutes + $durationMinutes;
        for ($cursor = $startMinutes; $cursor < $endMinutes; $cursor += 60) {
            if (! $windows->contains(fn (BookingTimeSlot $window) => $cursor >= $this->toMinutes((string) $window->start_time)
                && $cursor + 60 <= $this->toMinutes((string) $window->end_time))) {
                return false;
            }
        }

        return true;
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
