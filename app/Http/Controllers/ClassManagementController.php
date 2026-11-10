<?php

namespace App\\Http\\Controllers;

use App\\Models\\ClassSchedule;
use App\\Models\\Facility;
use App\\Models\\SessionParticipant;
use App\\Models\\SportClass;
use App\\Models\\Trainer;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;
use Inertia\\Inertia;
use Inertia\\Response;

class ClassManagementController extends Controller
{
    public function index(): Response
    {
        $classes = SportClass::query()
            ->with(['facility:id,name', 'schedules' => fn ($query) => $query
                ->with('trainer:id,name')
                ->withCount(['participants as registered_count' => fn ($participants) => $participants->whereIn('status', ['pending', 'confirmed'])])
                ->orderBy('session_date')->orderBy('start_time')])
            ->orderBy('name')
            ->get()
            ->map(fn (SportClass $class) => [
                'id' => $class->id,
                'facility_id' => $class->facility_id,
                'facility' => $class->facility?->name,
                'name' => $class->name,
                'description' => $class->description,
                'duration_minutes' => $class->duration_minutes,
                'default_capacity' => $class->default_capacity,
                'is_active' => (bool) $class->is_active,
                'schedules' => $class->schedules->map(fn (ClassSchedule $schedule) => [
                    'id' => $schedule->id,
                    'trainer_id' => $schedule->trainer_id,
                    'trainer' => $schedule->trainer?->name,
                    'date' => $schedule->session_date?->toDateString(),
                    'start_time' => substr((string) $schedule->start_time, 0, 5),
                    'end_time' => substr((string) $schedule->end_time, 0, 5),
                    'capacity' => $schedule->capacity,
                    'effective_capacity' => (int) ($schedule->capacity ?? $class->default_capacity),
                    'registered_count' => (int) $schedule->registered_count,
                    'price' => (float) $schedule->price,
                    'status' => $schedule->status,
                ])->values(),
            ]);

        return Inertia::render('classes/Index', [
            'classes' => $classes,
            'facilities' => Facility::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'trainers' => Trainer::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'default_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        SportClass::query()->create($data);

        return back()->with('success', 'Master kelas berhasil dibuat.');
    }

    public function update(Request $request, SportClass $sportClass): RedirectResponse
    {
        $data = $request->validate([
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'default_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        $sportClass->update($data);

        return back()->with('success', 'Master kelas berhasil diperbarui.');
    }

    public function destroy(SportClass $sportClass): RedirectResponse
    {
        if ($sportClass->schedules()->whereHas('participants')->exists()) {
            return back()->with('error', 'Kelas tidak dapat dihapus karena sudah memiliki riwayat pendaftaran.');
        }

        $sportClass->delete();

        return back()->with('success', 'Master kelas berhasil dihapus.');
    }

    public function storeSchedule(Request $request, SportClass $sportClass): RedirectResponse
    {
        $data = $this->validateSchedule($request);

        DB::transaction(function () use ($sportClass, $data) {
            $this->assertTrainerAvailable($sportClass, $data);
            $sportClass->schedules()->create($data);
        });

        return back()->with('success', 'Jadwal kelas berhasil dibuat.');
    }

    public function updateSchedule(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $data = $this->validateSchedule($request);
        $sportClass = SportClass::query()->findOrFail($schedule->class_id);
        $registeredCount = $schedule->participants()->whereIn('status', ['pending', 'confirmed'])->count();
        $newCapacity = $data['capacity'] ?? $sportClass->default_capacity;

        if ($newCapacity < $registeredCount) {
            throw ValidationException::withMessages([
                'capacity' => "Kapasitas tidak boleh lebih kecil dari {$registeredCount} peserta yang sudah terdaftar.",
            ]);
        }

        DB::transaction(function () use ($sportClass, $data, $schedule) {
            $this->assertTrainerAvailable($sportClass, $data, $schedule->id);
            $schedule->update([
                ...$data,
                'class_id' => $sportClass->id,
            ]);
        });

        return back()->with('success', 'Jadwal kelas berhasil diperbarui.');
    }

    public function destroySchedule(ClassSchedule $schedule): RedirectResponse
    {
        if ($schedule->participants()->exists()) {
            return back()->with('error', 'Jadwal memiliki peserta terdaftar. Ubah status menjadi dibatalkan agar riwayat tetap tersimpan.');
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal kelas berhasil dihapus.');
    }

    public function updateScheduleStatus(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:scheduled,completed,cancelled'],
        ]);

        if ($data['status'] === 'completed' && now()->lt($schedule->session_date->copy()->setTimeFromTimeString((string) $schedule->end_time))) {
            throw ValidationException::withMessages(['status' => 'Jadwal yang belum berlangsung tidak bisa ditandai selesai.']);
        }

        $schedule->update(['status' => $data['status']]);

        return back()->with('success', 'Status jadwal berhasil diperbarui.');
    }

    private function validateSchedule(Request $request): array
    {
        $data = $request->validate([
            'trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:scheduled,completed,cancelled'],
        ]);

        if ($data['end_time'] <= $data['start_time']) {
            throw ValidationException::withMessages([
                'end_time' => 'Jam selesai harus lebih besar dari jam mulai.',
            ]);
        }

        if ($data['status'] === 'completed') {
            $scheduledEnd = \\Illuminate\\Support\\Carbon::parse($data['session_date'].' '.$data['end_time']);
            if ($scheduledEnd->isFuture()) {
                throw ValidationException::withMessages([
                    'status' => 'Jadwal yang belum selesai tidak bisa ditandai completed.',
                ]);
            }
        }

        if ($data['session_date'] === today()->toDateString() && $data['end_time'] <= now()->format('H:i')) {
            // Past schedules can be edited for historical corrections, but not newly created.
        }

        return $data;
    }

    private function assertTrainerAvailable(SportClass $sportClass, array $data, ?int $ignoreScheduleId = null): void
    {
        if (empty($data['trainer_id'])) {
            return;
        }

        $facilityHasTrainer = $sportClass->facility()
            ->whereHas('trainers', fn ($query) => $query->whereKey($data['trainer_id']))
            ->exists();

        if (! $facilityHasTrainer) {
            throw ValidationException::withMessages([
                'trainer_id' => 'Trainer belum terhubung dengan fasilitas kelas ini.',
            ]);
        }

        Trainer::query()->whereKey($data['trainer_id'])->lockForUpdate()->firstOrFail();

        $conflict = ClassSchedule::query()
            ->where('trainer_id', $data['trainer_id'])
            ->whereDate('session_date', $data['session_date'])
            ->where('status', 'scheduled')
            ->when($ignoreScheduleId, fn ($query) => $query->whereKeyNot($ignoreScheduleId))
            ->whereTime('start_time', '<', $data['end_time'])
            ->whereTime('end_time', '>', $data['start_time'])
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'trainer_id' => 'Trainer sudah memiliki kelas lain pada waktu tersebut.',
            ]);
        }
    }
}
