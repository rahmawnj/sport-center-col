<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\SessionParticipant;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MemberClassController extends Controller
{
    public function index(): Response
    {
        $schedules = ClassSchedule::query()
            ->with(['sportClass.facility:id,name', 'trainer:id,name,specialty'])
            ->where('is_active', true)
            ->whereHas('sportClass', fn ($query) => $query->where('is_active', true)
                ->whereHas('facility', fn ($facility) => $facility->where('status', 'active')))
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->flatMap(function (ClassSchedule $schedule) {
                $dates = collect();
                $today = today();
                $dayNumber = Carbon::parse('next '.$schedule->day)->dayOfWeekIso;
                $offset = ($dayNumber - $today->dayOfWeekIso + 7) % 7;
                $firstDate = $today->copy()->addDays($offset);

                if ($offset === 0 && $schedule->start_time <= now()->format('H:i:s')) {
                    $firstDate->addWeek();
                }

                for ($week = 0; $week < 6; $week++) {
                    $date = $firstDate->copy()->addWeeks($week);
                    $registered = SessionParticipant::query()
                        ->where('class_schedule_id', $schedule->id)
                        ->whereDate('session_date', $date->toDateString())
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->count();
                    $capacity = (int) ($schedule->capacity ?? $schedule->sportClass?->default_capacity ?? 1);

                    $dates->push([
                        'id' => $schedule->id,
                        'session_date' => $date->toDateString(),
                        'class_name' => $schedule->sportClass?->name,
                        'description' => $schedule->sportClass?->description,
                        'facility' => $schedule->sportClass?->facility?->name,
                        'trainer' => $schedule->trainer?->name,
                        'trainer_specialty' => $schedule->trainer?->specialty,
                        'day' => $schedule->day,
                        'start_time' => substr((string) $schedule->start_time, 0, 5),
                        'end_time' => substr((string) $schedule->end_time, 0, 5),
                        'capacity' => $capacity,
                        'registered_count' => $registered,
                        'available_count' => max(0, $capacity - $registered),
                        'price' => (float) $schedule->price,
                    ]);
                }

                return $dates;
            })
            ->sortBy(fn ($item) => $item['session_date'].' '.$item['start_time'])
            ->values();

        return Inertia::render('member/Classes', ['schedules' => $schedules]);
    }


    public function publicIndex(Request $request): Response
    {
        $facilities = \\App\\Models\\Facility::query()
            ->where('status', 'active')
            ->where('is_bookable_online', true)
            ->with(['classes' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['schedules' => fn ($schedules) => $schedules
                    ->where('is_active', true)
                    ->with('trainer:id,name,specialty')
                    ->orderBy('day')
                    ->orderBy('start_time')])])
            ->orderBy('name')
            ->get()
            ->map(function ($facility) {
                $classes = $facility->classes->map(function ($sportClass) {
                    $sessions = $sportClass->schedules->flatMap(function (ClassSchedule $schedule) use ($sportClass) {
                        $dayNumber = Carbon::parse('next '.$schedule->day)->dayOfWeekIso;
                        $offset = ($dayNumber - today()->dayOfWeekIso + 7) % 7;
                        $firstDate = today()->copy()->addDays($offset);

                        if ($offset === 0 && $schedule->start_time <= now()->format('H:i:s')) {
                            $firstDate->addWeek();
                        }

                        return collect(range(0, 5))->map(function (int $week) use ($schedule, $sportClass, $firstDate) {
                            $date = $firstDate->copy()->addWeeks($week);
                            $registered = SessionParticipant::query()
                                ->where('class_schedule_id', $schedule->id)
                                ->whereDate('session_date', $date->toDateString())
                                ->whereIn('status', ['pending', 'confirmed'])
                                ->count();
                            $capacity = (int) ($schedule->capacity ?? $sportClass->default_capacity ?? 1);

                            return [
                                'id' => $schedule->id,
                                'session_date' => $date->toDateString(),
                                'day' => $schedule->day,
                                'start_time' => substr((string) $schedule->start_time, 0, 5),
                                'end_time' => substr((string) $schedule->end_time, 0, 5),
                                'trainer' => $schedule->trainer?->name,
                                'trainer_specialty' => $schedule->trainer?->specialty,
                                'capacity' => $capacity,
                                'registered_count' => $registered,
                                'available_count' => max(0, $capacity - $registered),
                                'price' => (float) $schedule->price,
                            ];
                        });
                    })->sortBy(fn ($session) => $session['session_date'].' '.$session['start_time'])->values();

                    return [
                        'id' => $sportClass->id,
                        'name' => $sportClass->name,
                        'description' => $sportClass->description,
                        'duration_minutes' => (int) $sportClass->duration_minutes,
                        'schedules' => $sessions,
                    ];
                })->filter(fn ($sportClass) => $sportClass['schedules']->isNotEmpty())->values();

                return [
                    'id' => $facility->id,
                    'name' => $facility->name,
                    'slug' => $facility->slug,
                    'classes' => $classes,
                ];
            })
            ->filter(fn ($facility) => $facility['classes']->isNotEmpty())
            ->values();

        return Inertia::render('classes/PublicIndex', [
            'facilities' => $facilities,
            'currentUser' => $request->user() ? [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ] : null,
            'success' => $request->session()->get('success'),
            'selectedScheduleId' => $request->integer('schedule') ?: null,
        ]);
    }

    public function storePublic(Request $request, ClassSchedule $schedule): RedirectResponse
    {
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

        $data = $request->validate(array_merge([
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', 'in:cash,bank_transfer'],
        ], $contactRules));

        DB::transaction(function () use ($request, $schedule, $data) {
            $lockedSchedule = ClassSchedule::query()
                ->with('sportClass.facility')
                ->lockForUpdate()
                ->findOrFail($schedule->id);

            abort_unless($lockedSchedule->is_active, 422, 'Jadwal kelas sedang tidak aktif.');
            abort_unless(
                $lockedSchedule->sportClass?->is_active
                    && $lockedSchedule->sportClass?->facility?->status === 'active'
                    && $lockedSchedule->sportClass?->facility?->is_bookable_online,
                422,
                'Kelas atau fasilitas sedang tidak tersedia untuk pendaftaran online.'
            );

            $sessionDate = Carbon::parse($data['session_date'])->startOfDay();
            abort_unless(strtolower($sessionDate->englishDayOfWeek) === $lockedSchedule->day, 422, 'Tanggal tidak sesuai dengan hari jadwal kelas.');
            abort_if($sessionDate->isToday() && $lockedSchedule->start_time <= now()->format('H:i:s'), 422, 'Pendaftaran untuk sesi yang sudah lewat tidak tersedia.');

            $user = $request->user();
            $guestPhone = $user ? ($data['guest_phone'] ?? null) : $data['guest_phone'];
            $duplicate = SessionParticipant::query()
                ->where('class_schedule_id', $lockedSchedule->id)
                ->whereDate('session_date', $sessionDate->toDateString())
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($user, $guestPhone) {
                    if ($user) {
                        $query->where('user_id', $user->id);
                    } else {
                        $query->whereNull('user_id')->where('guest_phone', $guestPhone);
                    }
                })
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'guest_phone' => 'Peserta ini sudah terdaftar untuk sesi kelas pada tanggal tersebut.',
                ]);
            }

            $capacity = (int) ($lockedSchedule->capacity ?? $lockedSchedule->sportClass->default_capacity ?? 1);
            $registered = SessionParticipant::query()
                ->where('class_schedule_id', $lockedSchedule->id)
                ->whereDate('session_date', $sessionDate->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            if ($registered >= $capacity) {
                throw ValidationException::withMessages([
                    'schedule' => 'Kuota sesi kelas sudah penuh. Silakan pilih tanggal lain.',
                ]);
            }

            $price = (float) $lockedSchedule->price;
            $participantName = $user?->name ?? $data['guest_name'];
            $transaction = Transaction::query()->create([
                'user_id' => $user?->id,
                'guest_name' => $participantName,
                'transactionable_type' => 'class',
                'total_amount' => $price,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
            ]);

            SessionParticipant::query()->create([
                'class_schedule_id' => $lockedSchedule->id,
                'session_date' => $sessionDate->toDateString(),
                'user_id' => $user?->id,
                'guest_name' => $participantName,
                'guest_email' => $user?->email ?? ($data['guest_email'] ?? null),
                'guest_phone' => $guestPhone,
                'transaction_id' => $transaction->id,
                'price' => $price,
                'status' => 'pending',
            ]);
        });

        return redirect()->route('classes.public')
            ->with('success', 'Pendaftaran kelas berhasil dibuat. Simpan bukti transaksi dan tunggu konfirmasi pembayaran dari pengelola.');
    }

    public function store(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,bank_transfer'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $transaction = DB::transaction(function () use ($request, $schedule, $data) {
            $lockedSchedule = ClassSchedule::query()
                ->with('sportClass.facility')
                ->lockForUpdate()
                ->findOrFail($schedule->id);

            abort_unless($lockedSchedule->is_active, 422, 'Jadwal kelas sedang tidak aktif.');
            abort_unless($lockedSchedule->sportClass?->is_active && $lockedSchedule->sportClass?->facility?->status === 'active', 422, 'Kelas atau fasilitas sedang tidak aktif.');

            $sessionDate = Carbon::parse($data['session_date'])->startOfDay();
            abort_unless(strtolower($sessionDate->englishDayOfWeek) === $lockedSchedule->day, 422, 'Tanggal tidak sesuai dengan hari jadwal kelas.');
            abort_if($sessionDate->isToday() && $lockedSchedule->start_time <= now()->format('H:i:s'), 422, 'Pendaftaran untuk sesi yang sudah lewat tidak tersedia.');

            $participant = SessionParticipant::query()
                ->where('class_schedule_id', $lockedSchedule->id)
                ->where('session_date', $sessionDate->toDateString())
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['pending', 'confirmed', 'attended', 'no_show'])
                ->lockForUpdate()
                ->first();

            if ($participant && $participant->status !== 'cancelled') {
                throw ValidationException::withMessages([
                    'schedule' => 'Kamu sudah terdaftar atau memiliki pendaftaran yang menunggu pembayaran untuk sesi ini.',
                ]);
            }

            $capacity = (int) ($lockedSchedule->capacity ?? $lockedSchedule->sportClass->default_capacity ?? 1);
            $registered = SessionParticipant::query()
                ->where('class_schedule_id', $lockedSchedule->id)
                ->where('session_date', $sessionDate->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            if ($registered >= $capacity) {
                throw ValidationException::withMessages([
                    'schedule' => 'Kuota sesi kelas sudah penuh. Silakan pilih tanggal lain.',
                ]);
            }

            $user = $request->user();
            $price = (float) $lockedSchedule->price;
            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'guest_name' => $user->name,
                'transactionable_type' => 'class',
                'total_amount' => $price,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
            ]);

            SessionParticipant::query()->create([
                'class_schedule_id' => $lockedSchedule->id,
                'session_date' => $sessionDate->toDateString(),
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'price' => $price,
                'status' => 'pending',
            ]);

            return $transaction;
        });

        return redirect()->route('member.payments.show', $transaction->id)
            ->with('success', 'Pendaftaran kelas berhasil dibuat. Selesaikan pembayaran untuk mengonfirmasi tempatmu.');
    }
}
