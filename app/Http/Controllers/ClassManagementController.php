<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\Facility;
use App\Models\SessionParticipant;
use App\Models\SportClass;
use App\Models\Trainer;
use App\Models\TrainerSessionSchedule;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClassManagementController extends Controller
{
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function index(): Response
    {
        $classes = SportClass::query()
            ->with(['facility:id,name', 'schedules' => fn ($query) => $query
                ->with(['trainer:id,name', 'participants.user:id,name,email'])
                ->withCount(['participants as registered_count' => fn ($participants) => $participants->whereIn('status', ['pending', 'confirmed'])])
                ->orderBy('day')->orderBy('start_time')])
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
                    'day' => $schedule->day,
                    'start_time' => substr((string) $schedule->start_time, 0, 5),
                    'end_time' => substr((string) $schedule->end_time, 0, 5),
                    'capacity' => $schedule->capacity,
                    'effective_capacity' => (int) ($schedule->capacity ?? $class->default_capacity),
                    'registered_count' => (int) $schedule->registered_count,
                    'price' => (float) $schedule->price,
                    'is_active' => (bool) $schedule->is_active,
                    'participants' => $schedule->participants->map(fn (SessionParticipant $participant) => [
                        'id' => $participant->id,
                        'name' => $participant->user?->name ?? 'Member',
                        'email' => $participant->user?->email,
                        'session_date' => $participant->session_date?->toDateString(),
                        'status' => $participant->status,
                    ])->values(),
                ])->values(),
            ]);

        return Inertia::render('classes/Index', [
            'classes' => $classes,
            'facilities' => Facility::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'trainers' => Trainer::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, Facility $facility): RedirectResponse
    {
        abort_unless($facility->status === 'active', 422, 'Kelas hanya dapat ditambahkan pada fasilitas aktif.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'default_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        $facility->classes()->create($data);

        return back()->with('success', 'Master kelas berhasil dibuat.');
    }

    public function update(Request $request, SportClass $sportClass): RedirectResponse
    {
        $data = $request->validate([
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
        $this->assertClassDuration($sportClass, $data);

        DB::transaction(function () use ($sportClass, $data) {
            $this->assertTrainerAvailable($sportClass, $data);
            $sportClass->schedules()->create($data);
        });

        return back()->with('success', 'Jadwal rutin mingguan berhasil dibuat.');
    }

    public function updateSchedule(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $data = $this->validateSchedule($request);
        $sportClass = SportClass::query()->findOrFail($schedule->class_id);
        $this->assertClassDuration($sportClass, $data);

        $newCapacity = $data['capacity'] ?? $sportClass->default_capacity;
        $maxRegisteredOnOneDate = $schedule->participants()
            ->whereIn('status', ['pending', 'confirmed'])
            ->selectRaw('session_date, COUNT(*) as participant_count')
            ->groupBy('session_date')
            ->orderByDesc('participant_count')
            ->value('participant_count') ?? 0;

        if ($newCapacity < $maxRegisteredOnOneDate) {
            throw ValidationException::withMessages([
                'capacity' => "Kapasitas tidak boleh lebih kecil dari {$maxRegisteredOnOneDate} peserta pada satu sesi.",
            ]);
        }

        DB::transaction(function () use ($sportClass, $data, $schedule) {
            $this->assertTrainerAvailable($sportClass, $data, $schedule->id);
            $schedule->update([...$data, 'class_id' => $sportClass->id]);
        });

        return back()->with('success', 'Jadwal rutin mingguan berhasil diperbarui.');
    }

    public function destroySchedule(ClassSchedule $schedule): RedirectResponse
    {
        if ($schedule->participants()->exists()) {
            return back()->with('error', 'Jadwal ini memiliki riwayat peserta. Nonaktifkan jadwal agar riwayat tetap tersimpan.');
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    public function updateScheduleStatus(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $schedule->update(['is_active' => $data['is_active']]);

        return back()->with('success', $data['is_active'] ? 'Jadwal rutin diaktifkan.' : 'Jadwal rutin dinonaktifkan.');
    }

    public function updateParticipantAttendance(Request $request, SessionParticipant $participant): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:attended,no_show']]);

        DB::transaction(function () use ($participant, $data) {
            $locked = SessionParticipant::query()->with('schedule')->lockForUpdate()->findOrFail($participant->id);
            $schedule = $locked->schedule;

            abort_unless($schedule, 404);
            $sessionEnd = Carbon::parse($locked->session_date->toDateString().' '.$schedule->end_time);
            abort_unless(now()->gte($sessionEnd), 422, 'Kehadiran hanya dapat dicatat setelah sesi selesai.');
            abort_unless(in_array($locked->status, ['confirmed', 'attended', 'no_show'], true), 422, 'Peserta belum dikonfirmasi pembayarannya.');

            $locked->update(['status' => $data['status']]);
        });

        return back()->with('success', 'Kehadiran peserta berhasil diperbarui.');
    }

    private function validateSchedule(Request $request): array
    {
        $data = $request->validate([
            'trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
            'day' => ['required', 'in:'.implode(',', self::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($data['end_time'] <= $data['start_time']) {
            throw ValidationException::withMessages(['end_time' => 'Jam selesai harus lebih besar dari jam mulai.']);
        }

        return $data;
    }

    private function assertClassDuration(SportClass $sportClass, array $data): void
    {
        $start = Carbon::createFromFormat('H:i', $data['start_time']);
        $expectedEnd = $start->copy()->addMinutes((int) $sportClass->duration_minutes)->format('H:i');

        if ($data['end_time'] !== $expectedEnd) {
            throw ValidationException::withMessages([
                'end_time' => "Durasi kelas ini {$sportClass->duration_minutes} menit. Jam selesai seharusnya {$expectedEnd}.",
            ]);
        }
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
            throw ValidationException::withMessages(['trainer_id' => 'Trainer belum terhubung dengan fasilitas kelas ini.']);
        }

        Trainer::query()->whereKey($data['trainer_id'])->lockForUpdate()->firstOrFail();

        $classConflict = ClassSchedule::query()
            ->where('trainer_id', $data['trainer_id'])
            ->where('day', $data['day'])
            ->where('is_active', true)
            ->when($ignoreScheduleId, fn ($query) => $query->where('id', '!=', $ignoreScheduleId))
            ->whereTime('start_time', '<', $data['end_time'])
            ->whereTime('end_time', '>', $data['start_time'])
            ->exists();

        $dates = collect(range(0, 83))
            ->map(fn ($offset) => today()->addDays($offset))
            ->filter(fn (Carbon $date) => strtolower($date->englishDayOfWeek) === $data['day'])
            ->map(fn (Carbon $date) => $date->toDateString())
            ->all();

        $personalTrainingConflict = TrainerSessionSchedule::query()
            ->whereIn('session_date', $dates)
            ->where('status', 'scheduled')
            ->whereHas('memberTrainerPackage', fn ($query) => $query
                ->where('trainer_id', $data['trainer_id'])
                ->whereIn('status', ['pending', 'active']))
            ->whereTime('start_time', '<', $data['end_time'])
            ->whereTime('end_time', '>', $data['start_time'])
            ->exists();

        if ($classConflict || $personalTrainingConflict) {
            throw ValidationException::withMessages(['trainer_id' => 'Trainer sudah memiliki jadwal lain pada hari dan waktu tersebut.']);
        }
    }
}
