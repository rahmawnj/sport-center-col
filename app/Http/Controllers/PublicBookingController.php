<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\Package;
use App\Models\PackagePricingRule;
use App\Models\UserMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
            ->where('is_bookable_online', true)
            ->with(['packages' => fn ($query) => $query->orderBy('name')->with('pricingRules')])
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
                    'pricing_rules' => $package->pricingRules
                        ->filter(fn ($rule) => $this->isActiveOn($rule, Carbon::today()->toDateString()))
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
            'initialFacilityId' => $initialFacility['id'] ?? null,
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

        $courts = $package->facility->courts
            ->map(fn ($court) => [
                'id' => $court->id,
                'name' => $court->name,
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
            'court_id' => ['nullable', 'integer', 'exists:courts,id'],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:50'],
        ]);

        $package = Package::with(['pricingRules', 'facility'])->findOrFail($data['package_id']);

        if ($package->type === 'session') {
            $this->storeBooking($request, $package, $data);
        } else {
            $this->storeDirect($request, $package, $data);
        }

        return redirect()->route('booking.index')->with('success', 'Booking berhasil dibuat. Silakan selesaikan pembayaran.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeBooking(Request $request, Package $package, array $data): void
    {
        if (empty($data['court_id']) || empty($data['date']) || empty($data['start_time'])) {
            throw ValidationException::withMessages(['start_time' => 'Lapangan, tanggal, dan jam wajib dipilih.']);
        }

        $date = Carbon::parse($data['date']);
        $startMinutes = $this->toMinutes($data['start_time']);
        $endMinutes = $startMinutes + max(1, (int) $package->duration_minutes);
        $turnaround = max(0, (int) $package->facility->turnaround_minutes);

        if ($date->isToday() && $startMinutes < (now()->hour * 60 + now()->minute)) {
            throw ValidationException::withMessages(['start_time' => 'Jam yang dipilih sudah lewat.']);
        }

        $price = $this->resolvePrice($this->matchingRules($package, $date), $startMinutes);

        if ($price === null) {
            throw ValidationException::withMessages(['start_time' => 'Jam yang dipilih tidak memiliki tarif.']);
        }

        DB::transaction(function () use ($request, $data, $date, $startMinutes, $endMinutes, $turnaround, $price) {
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

            $booking = Booking::create([
                'user_id' => $user?->id,
                'guest_name' => $user ? null : $data['guest_name'],
                'guest_email' => $user ? null : $data['guest_email'],
                'guest_phone' => $user ? null : $data['guest_phone'],
                'court_id' => $data['court_id'],
                'date' => $date->toDateString(),
                'start_time' => $this->toTime($startMinutes),
                'end_time' => $this->toTime($endMinutes),
                'status' => 'pending',
            ]);

            $booking->transactions()->create([
                'user_id' => $user?->id,
                'amount' => $price,
                'payment_status' => 'pending',
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

            $membership = UserMembership::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'active',
            ]);

            $membership->transactions()->create([
                'user_id' => $user->id,
                'amount' => $price,
                'payment_status' => 'pending',
            ]);

            return;
        }

        $package->transactions()->create([
            'user_id' => $user?->id,
            'customer_name' => $user ? null : $data['guest_name'],
            'amount' => $price,
            'payment_status' => 'pending',
        ]);
    }

    private function basePrice(Package $package, Carbon $date): ?float
    {
        $rules = $this->matchingRules($package, $date);

        if ($rules->isEmpty()) {
            $rules = $package->pricingRules->filter(
                fn ($rule) => $this->isActiveOn($rule, $date->toDateString()),
            );
        }

        return $rules->isEmpty() ? null : (float) $rules->min('price');
    }

    /**
     * @return array<int, array{start_time: string, end_time: string, price: float, courts: array<int, array{id: int, name: string}>}>
     */
    private function buildSlots(Package $package, Carbon $date): array
    {
        $rules = $this->matchingRules($package, $date);

        if ($rules->isEmpty()) {
            return [];
        }

        $open = $rules->pluck('start_time')->filter()->min() ?? self::DEFAULT_OPEN;
        $close = $rules->pluck('end_time')->filter()->max() ?? self::DEFAULT_CLOSE;
        $duration = max(1, (int) $package->duration_minutes);
        $turnaround = max(0, (int) $package->facility->turnaround_minutes);
        $step = $duration + $turnaround;

        $courts = $package->facility->courts
            ->where('status', 'available')
            ->values();

        $bookings = Booking::query()
            ->whereIn('court_id', $courts->pluck('id'))
            ->whereDate('date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->get(['court_id', 'start_time', 'end_time']);

        $slots = [];
        $cursor = $this->toMinutes((string) $open);
        $end = $this->toMinutes((string) $close);

        while ($cursor + $duration <= $end) {
            $price = $this->resolvePrice($rules, $cursor);

            if ($price !== null) {
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
                    ->map(fn ($court) => ['id' => $court->id, 'name' => $court->name])
                    ->values();

                $slots[] = [
                    'start_time' => $this->toTime($cursor),
                    'end_time' => $this->toTime($slotEnd),
                    'price' => (float) $price,
                    'courts' => $available,
                ];
            }

            $cursor += $step;
        }

        return $slots;
    }

    /**
     * Two bookings clash when their occupied windows overlap. Each booking occupies
     * [start, end] for play plus `turnaround` minutes afterwards for preparation.
     */
    private function windowsOverlap(int $start, int $end, int $otherStart, int $otherEnd, int $turnaround): bool
    {
        return $start < ($otherEnd + $turnaround) && ($end + $turnaround) > $otherStart;
    }

    /**
     * Rules that apply on the given date (day type + promo date range), sorted by
     * priority first, then by the narrowest time window so promos win over base rates.
     *
     * @return Collection<int, PackagePricingRule>
     */
    private function matchingRules(Package $package, Carbon $date): Collection
    {
        $dayType = $date->isWeekend() ? 'weekend' : 'weekday';
        $dateString = $date->toDateString();

        return $package->pricingRules
            ->filter(fn ($rule) => in_array($rule->day_type, [$dayType, 'all'], true)
                && $this->isActiveOn($rule, $dateString))
            ->sortBy([
                fn ($a, $b) => $b->priority <=> $a->priority,
                fn ($a, $b) => $this->windowWidth($a) <=> $this->windowWidth($b),
            ])
            ->values();
    }

    private function resolvePrice(Collection $rules, int $startMinutes): ?float
    {
        $rule = $rules->first(function ($rule) use ($startMinutes) {
            $ruleStart = $rule->start_time ? $this->toMinutes((string) $rule->start_time) : 0;
            $ruleEnd = $rule->end_time ? $this->toMinutes((string) $rule->end_time) : 1440;

            return $startMinutes >= $ruleStart && $startMinutes < $ruleEnd;
        });

        return $rule ? (float) $rule->price : null;
    }

    private function isActiveOn($rule, string $dateString): bool
    {
        return ($rule->date_start === null || $rule->date_start->toDateString() <= $dateString)
            && ($rule->date_end === null || $rule->date_end->toDateString() >= $dateString);
    }

    private function windowWidth($rule): int
    {
        $start = $rule->start_time ? $this->toMinutes((string) $rule->start_time) : 0;
        $end = $rule->end_time ? $this->toMinutes((string) $rule->end_time) : 1440;

        return $end - $start;
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
