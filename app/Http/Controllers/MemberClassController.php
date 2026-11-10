<?php

namespace App\\Http\\Controllers;

use App\\Models\\ClassSchedule;
use App\\Models\\SessionParticipant;
use App\\Models\\SportClass;
use App\\Models\\Transaction;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;
use Inertia\\Inertia;
use Inertia\\Response;

class MemberClassController extends Controller
{
    public function index(): Response
    {
        $schedules = ClassSchedule::query()
            ->with(['sportClass.facility:id,name', 'trainer:id,name,specialty'])
            ->withCount(['participants as registered_count' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed'])])
            ->where('status', 'scheduled')
            ->where(function ($query) {
                $query->whereDate('session_date', '>', today())
                    ->orWhere(function ($todayQuery) {
                        $todayQuery->whereDate('session_date', today())
                            ->whereTime('start_time', '>', now()->format('H:i:s'));
                    });
            })
            ->whereHas('sportClass', fn ($query) => $query->where('is_active', true))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSchedule $schedule) => [
                'id' => $schedule->id,
                'class_name' => $schedule->sportClass?->name,
                'description' => $schedule->sportClass?->description,
                'facility' => $schedule->sportClass?->facility?->name,
                'trainer' => $schedule->trainer?->name,
                'trainer_specialty' => $schedule->trainer?->specialty,
                'date' => $schedule->session_date?->toDateString(),
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'capacity' => (int) ($schedule->capacity ?? $schedule->sportClass?->default_capacity ?? 1),
                'registered_count' => (int) $schedule->registered_count,
                'available_count' => max(0, (int) ($schedule->capacity ?? $schedule->sportClass?->default_capacity ?? 1) - (int) $schedule->registered_count),
                'price' => (float) $schedule->price,
            ]);

        return Inertia::render('member/Classes', ['schedules' => $schedules]);
    }

    public function store(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,bank_transfer'],
        ]);

        $transaction = DB::transaction(function () use ($request, $schedule, $data) {
            $lockedSchedule = ClassSchedule::query()
                ->with('sportClass')
                ->lockForUpdate()
                ->findOrFail($schedule->id);

            abort_unless($lockedSchedule->status === 'scheduled', 422, 'Jadwal kelas tidak menerima pendaftaran.');
            abort_if(
                $lockedSchedule->session_date->lt(today()) ||
                ($lockedSchedule->session_date->isToday() && $lockedSchedule->start_time <= now()->format('H:i:s')),
                422,
                'Pendaftaran untuk jadwal yang sudah lewat tidak tersedia.'
            );

            $participant = SessionParticipant::query()
                ->where('class_schedule_id', $lockedSchedule->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if ($participant && $participant->status !== 'cancelled') {
                throw ValidationException::withMessages([
                    'schedule' => 'Kamu sudah terdaftar atau memiliki pendaftaran yang menunggu pembayaran untuk kelas ini.',
                ]);
            }

            $capacity = (int) ($lockedSchedule->capacity ?? $lockedSchedule->sportClass->default_capacity ?? 1);
            $registered = SessionParticipant::query()
                ->where('class_schedule_id', $lockedSchedule->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            if ($registered >= $capacity) {
                throw ValidationException::withMessages([
                    'schedule' => 'Kuota kelas sudah penuh. Silakan pilih jadwal lain.',
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

            $participantData = [
                'transaction_id' => $transaction->id,
                'price' => $price,
                'status' => 'pending',
            ];

            if ($participant) {
                $participant->update($participantData);
            } else {
                SessionParticipant::query()->create([
                    'class_schedule_id' => $lockedSchedule->id,
                    'user_id' => $user->id,
                    ...$participantData,
                ]);
            }

            return $transaction;
        });

        return redirect()->route('member.payments.show', $transaction->id)
            ->with('success', 'Pendaftaran kelas berhasil dibuat. Selesaikan pembayaran untuk mengonfirmasi tempatmu.');
    }
}
