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
