<?php

namespace App\Http\Controllers;

use App\Models\BookingTimeSlot;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingTimeSlotController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('booking-time-slots/Index', [
            'facilities' => Facility::query()
                ->where('status', '!=', 'inactive')
                ->orderBy('name')
                ->get(['id', 'name', 'turnaround_minutes']),
            'slots' => BookingTimeSlot::query()
                ->with('facility:id,name,turnaround_minutes')
                ->orderBy('facility_id')
                ->orderBy('start_time')
                ->get()
                ->map(fn (BookingTimeSlot $slot) => [
                    'id' => $slot->id,
                    'facility_id' => $slot->facility_id,
                    'facility_name' => $slot->facility?->name,
                    'start_time' => substr((string) $slot->start_time, 0, 5),
                    'end_time' => substr((string) $slot->end_time, 0, 5),
                    'is_active' => $slot->is_active,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->ensureNoConflict($data);
        BookingTimeSlot::create($data);

        return back()->with('success', 'Master jam booking berhasil ditambahkan.');
    }

    public function update(Request $request, BookingTimeSlot $bookingTimeSlot): RedirectResponse
    {
        $data = $request->validate([
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->ensureNoConflict($data, $bookingTimeSlot->id);
        $bookingTimeSlot->update($data);

        return back()->with('success', 'Master jam booking berhasil diperbarui.');
    }

    public function destroy(BookingTimeSlot $bookingTimeSlot): RedirectResponse
    {
        $bookingTimeSlot->delete();

        return back()->with('success', 'Master jam booking berhasil dihapus.');
    }

    private function ensureNoConflict(array $data, ?int $ignoreId = null): void
    {
        if (! $data['is_active']) {
            return;
        }

        $turnaround = max(0, (int) Facility::query()->whereKey($data['facility_id'])->value('turnaround_minutes'));
        $start = $this->toMinutes($data['start_time']);
        $end = $this->toMinutes($data['end_time']);

        $conflict = BookingTimeSlot::query()
            ->where('facility_id', $data['facility_id'])
            ->where('is_active', true)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get(['start_time', 'end_time'])
            ->contains(function (BookingTimeSlot $slot) use ($start, $end, $turnaround) {
                $otherStart = $this->toMinutes((string) $slot->start_time);
                $otherEnd = $this->toMinutes((string) $slot->end_time);

                return $start < ($otherEnd + $turnaround)
                    && ($end + $turnaround) > $otherStart;
            });

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_time' => "Rentang jam bertabrakan atau terlalu mepet. Beri jeda minimal {$turnaround} menit sesuai pengaturan fasilitas.",
            ]);
        }
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours * 60) + (int) $minutes;
    }
}
