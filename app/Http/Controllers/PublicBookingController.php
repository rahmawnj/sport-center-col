<?php

namespace App\Http\Controllers;

use App\Models\AddOn;
use App\Models\Booking;
use App\Models\PricingRate;
use App\Models\Zone;
use App\Models\ZoneSpace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    public function index(Request $request): Response
    {
        $zones = Zone::query()
            ->where('is_online_bookable', true)
            ->with(['zoneSpaces' => fn ($query) => $query
                ->whereNull('deleted_at')
                ->with(['pricingRates', 'facilities'])])
            ->orderBy('name')
            ->get()
            ->map(fn (Zone $zone) => [
                'id' => $zone->id,
                'name' => $zone->name,
                'pricing_model' => $zone->pricing_model,
                'spaces' => $zone->zoneSpaces
                    ->map(fn (ZoneSpace $space) => [
                        'id' => $space->id,
                        'name' => $space->name,
                        'capacity' => $space->capacity,
                        'status' => $space->status,
                        'facilities' => $space->facilities->pluck('name')->values(),
                        'rates' => $space->pricingRates->map(fn (PricingRate $rate) => [
                            'id' => $rate->id,
                            'rental_type' => $rate->rental_type,
                            'price' => (float) $rate->price,
                            'unit_type' => $rate->unit_type,
                            'min_booking_duration' => max(1, (int) $rate->min_booking_duration),
                        ])->values(),
                    ])->values(),
            ])->values();

        $addOns = AddOn::query()
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock'])
            ->map(fn (AddOn $addOn) => [
                'id' => $addOn->id,
                'name' => $addOn->name,
                'price' => (float) $addOn->price,
                'stock' => (int) $addOn->stock,
            ])->values();

        return Inertia::render('book/Index', [
            'zones' => $zones,
            'addOns' => $addOns,
            'initialZoneId' => $request->integer('zone_id') ?: null,
            'initialSpaceId' => $request->integer('space_id') ?: null,
            'successMessage' => session('success'),
            'bookingReference' => session('booking_reference'),
        ]);
    }

    public function addOnsStock(): JsonResponse
    {
        $addOns = AddOn::query()
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock'])
            ->map(fn (AddOn $addOn) => [
                'id' => $addOn->id,
                'name' => $addOn->name,
                'price' => (float) $addOn->price,
                'stock' => (int) $addOn->stock,
            ])->values();

        return response()->json(['addOns' => $addOns])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'space_id' => ['required', 'integer', 'exists:zone_spaces,id'],
            'rate_id' => ['required', 'integer', 'exists:pricing_rates,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'duration' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $space = ZoneSpace::query()->with('zone')->findOrFail($data['space_id']);
        $rate = PricingRate::query()->where('zone_space_id', $space->id)->findOrFail($data['rate_id']);

        if ($space->status !== 'available' || ! $space->zone?->is_online_bookable) {
            return response()->json(['slots' => []]);
        }

        $date = Carbon::parse($data['date']);
        $duration = max((int) $rate->min_booking_duration, (int) $data['duration']);
        $minutes = $this->durationMinutes($rate, $duration);
        [$open, $close] = $this->openingWindow($space->zone_id, $date);
        // Availability is based on operational hours first. Do not query bookings
        // until booking persistence is configured in the database.
        $slots = [];

        for ($start = $open; $start + $minutes <= $close; $start += 60) {
            if ($date->isToday() && $start < now()->hour * 60 + now()->minute) {
                continue;
            }

            $end = $start + $minutes;
            $slots[] = [
                'start_time' => $this->toTime($start),
                'end_time' => $this->toTime($end),
                'price' => $this->calculateRatePrice($rate, $duration),
            ];
        }

        return response()->json(['slots' => $slots]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'space_id' => ['required', 'integer', 'exists:zone_spaces,id'],
            'rate_id' => ['required', 'integer', 'exists:pricing_rates,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', 'min:1', 'max:12'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:50'],
            'add_ons' => ['nullable', 'array'],
            'add_ons.*.id' => ['required', 'integer', 'distinct', 'exists:add_ons,id'],
            'add_ons.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $space = ZoneSpace::query()->with('zone')->findOrFail($data['space_id']);
        $rate = PricingRate::query()->where('zone_space_id', $space->id)->findOrFail($data['rate_id']);
        $date = Carbon::parse($data['date']);
        $duration = max((int) $rate->min_booking_duration, (int) $data['duration']);
        $start = $this->toMinutes($data['start_time']);
        $end = $start + $this->durationMinutes($rate, $duration);
        [$open, $close] = $this->openingWindow($space->zone_id, $date);

        if (! $space->zone?->is_online_bookable || $space->status !== 'available') {
            throw ValidationException::withMessages(['space_id' => 'Ruang ini sedang tidak bisa dipesan.']);
        }
        if ($start < $open || $end > $close || ($date->isToday() && $start < now()->hour * 60 + now()->minute)) {
            throw ValidationException::withMessages(['start_time' => 'Jam yang dipilih di luar jam operasional atau sudah lewat.']);
        }

        $selectedAddOns = collect($data['add_ons'] ?? []);
        $addOnModels = AddOn::query()->whereIn('id', $selectedAddOns->pluck('id'))->get()->keyBy('id');
        foreach ($selectedAddOns as $item) {
            $addOn = $addOnModels->get((int) $item['id']);
            if (! $addOn || $addOn->stock < (int) $item['quantity']) {
                throw ValidationException::withMessages(['add_ons' => 'Stok add-on berubah. Silakan sesuaikan pilihanmu.']);
            }
        }

        $booking = DB::transaction(function () use ($data, $space, $rate, $date, $duration, $start, $end, $selectedAddOns, $addOnModels) {
            $conflict = $this->bookingsForSpace($space->id, $date, true)->contains(
                fn ($booking) => $this->overlaps(
                    $start,
                    $end,
                    $this->toMinutes((string) $booking->start_time),
                    $this->toMinutes((string) $booking->end_time),
                )
            );

            if ($conflict) {
                throw ValidationException::withMessages(['start_time' => 'Jadwal ini baru saja dipesan. Silakan pilih jam lain.']);
            }

            $basePrice = $this->calculateRatePrice($rate, $duration);
            $addOnTotal = 0;
            foreach ($selectedAddOns as $item) {
                $addOn = $addOnModels->get((int) $item['id']);
                $quantity = (int) $item['quantity'];
                $addOnHours = $this->isHourlyRate($rate) ? $duration : 1;\n                $addOnTotal += (float) $addOn->price * $quantity * $addOnHours;
                $addOn->decrement('stock', $quantity);
            }

            $booking = Booking::create([
                'user_id' => null,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'guest_phone' => $data['guest_phone'],
                'court_id' => null,
                'zone_space_id' => $space->id,
                'pricing_rate_id' => $rate->id,
                'date' => $date->toDateString(),
                'start_time' => $this->toTime($start),
                'end_time' => $this->toTime($end),
                'status' => 'pending',
            ]);

            foreach ($selectedAddOns as $item) {
                $addOn = $addOnModels->get((int) $item['id']);
                DB::table('booking_add_ons')->insert([
                    'booking_id' => $booking->id,
                    'add_on_id' => $addOn->id,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $addOn->price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $booking->transactions()->create([
                'user_id' => null,
                'customer_name' => $data['guest_name'],
                'amount' => $basePrice + $addOnTotal,
                'payment_status' => 'pending',
            ]);

            return $booking;
        });

        return redirect()->route('booking.index')->with([
            'success' => 'Booking berhasil dicatat. Simpan kode booking berikut untuk referensi.',
            'booking_reference' => 'BK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
        ]);
    }

    private function openingWindow(int $zoneId, Carbon $date): array
    {
        if (Schema::hasTable('operational_hours')) {
            $hours = DB::table('operational_hours')->where('zone_id', $zoneId)->get();
            $dayName = strtolower($date->englishDayOfWeek);
            $row = $hours->first(function ($item) use ($date, $dayName) {
                $day = strtolower((string) $item->day_of_week);
                return $day === (string) $date->dayOfWeek
                    || $day === $dayName
                    || $day === strtolower(substr($dayName, 0, 3));
            });

            if ($row) {
                if ((bool) $row->is_closed) {
                    throw ValidationException::withMessages(['date' => 'Zona tutup pada tanggal yang dipilih.']);
                }

                return [$this->toMinutes(substr((string) $row->open_time, 0, 5)), $this->toMinutes(substr((string) $row->close_time, 0, 5))];
            }
        }

        return [8 * 60, 23 * 60];
    }

    private function bookingsForSpace(int $spaceId, Carbon $date, bool $lock = false)
    {
        $query = Booking::query()
            ->where('zone_space_id', $spaceId)
            ->whereDate('date', $date->toDateString())
            ->whereNotIn('status', ['cancelled', 'rejected']);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get(['start_time', 'end_time']);
    }

    private function calculateRatePrice(PricingRate $rate, int $duration): float
    {
        return (float) $rate->price * ($this->isHourlyRate($rate) ? $duration : 1);
    }

    private function isHourlyRate(PricingRate $rate): bool
    {
        return $rate->unit_type === 'per_hour'
            || ($rate->unit_type === 'per_session' && preg_match('/jam|hour/i', (string) $rate->rental_type) === 1);
    }

    private function durationMinutes(PricingRate $rate, int $duration): int
    {
        return $this->isHourlyRate($rate)
            ? $duration * 60
            : max(1, (int) $rate->min_booking_duration) * 60;
    }

    private function overlaps(int $start, int $end, int $otherStart, int $otherEnd): bool
    {
        return $start < $otherEnd && $end > $otherStart;
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');
        return ((int) $hours * 60) + (int) $minutes;
    }

    private function toTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
    }
}
