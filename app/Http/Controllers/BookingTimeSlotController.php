<?php

namespace App\Http\Controllers;

use App\Models\BookingTimeSlot;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingTimeSlotController extends Controller
{
    public function store(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate([
            'day_type' => ['required', 'in:all,weekday,weekend'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['facility_id'] = $facility->id;
        $this->ensureNoConflict($data);
        BookingTimeSlot::create($data);

        return back()->with('success', 'Master jam booking berhasil ditambahkan.');
    }

    public function update(Request $request, Facility $facility, BookingTimeSlot $bookingTimeSlot): RedirectResponse
    {
        $data = $request->validate([
            'day_type' => ['sometimes', 'required', 'in:all,weekday,weekend'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['required', 'boolean'],
        ]);

        abort_unless($bookingTimeSlot->facility_id === $facility->id, 404);

        // Keep the existing day type when an older client omits this field.
        $data['day_type'] ??= $bookingTimeSlot->day_type;
        $data['facility_id'] = $facility->id;
        $this->ensureNoConflict($data, $bookingTimeSlot->id);
        $bookingTimeSlot->update($data);

        return back()->with('success', 'Master jam booking berhasil diperbarui.');
    }

    public function destroy(Facility $facility, BookingTimeSlot $bookingTimeSlot): RedirectResponse
    {
        abort_unless($bookingTimeSlot->facility_id === $facility->id, 404);
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
            ->whereIn('day_type', $data['day_type'] === 'all' ? ['all', 'weekday', 'weekend'] : ['all', $data['day_type']])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get(['day_type', 'start_time', 'end_time'])
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
