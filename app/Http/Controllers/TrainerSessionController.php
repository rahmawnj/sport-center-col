<?php

namespace App\Http\Controllers;

use App\Models\MemberTrainerPackage;
use App\Models\Trainer;
use App\Models\TrainerSessionSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainerSessionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'member_trainer_package_id' => ['required', 'integer', 'exists:member_trainer_packages,id'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        DB::transaction(function () use ($data) {
            $package = MemberTrainerPackage::query()->lockForUpdate()->findOrFail($data['member_trainer_package_id']);
            abort_unless($package->isActive(), 422, 'Paket trainer tidak sedang aktif atau kuotanya habis.');
            abort_unless($data['session_date'] >= $package->start_date->toDateString() && $data['session_date'] <= $package->expires_at->toDateString(), 422, 'Tanggal sesi harus berada dalam periode paket trainer.');

            $overlap = TrainerSessionSchedule::query()
                ->where('session_date', $data['session_date'])
                ->where('status', 'scheduled')
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->whereHas('memberTrainerPackage', fn ($q) => $q->where(function ($sub) use ($package) {
                    $sub->where('trainer_id', $package->trainer_id)->orWhere('user_id', $package->user_id);
                }))
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages(['start_time' => 'Jadwal bertabrakan dengan sesi member atau trainer lain.']);
            }

            TrainerSessionSchedule::create([
                ...$data,
                'status' => 'scheduled',
                'member_attended' => false,
                'trainer_attended' => false,
            ]);
        });

        return back()->with('success', 'Jadwal sesi berhasil dibuat. Member dan trainer dapat melihat serta mengonfirmasi kehadiran setelah sesi berakhir.');
    }

    public function confirmMember(Request $request, TrainerSessionSchedule $schedule): RedirectResponse
    {
        $this->confirm($request, $schedule, 'member');
        return back()->with('success', 'Konfirmasi kehadiran member tersimpan.');
    }

    public function confirmTrainer(Request $request, TrainerSessionSchedule $schedule): RedirectResponse
    {
        $this->confirm($request, $schedule, 'trainer');
        return back()->with('success', 'Konfirmasi kehadiran trainer tersimpan.');
    }

    private function confirm(Request $request, TrainerSessionSchedule $schedule, string $role): void
    {
        DB::transaction(function () use ($request, $schedule, $role) {
            $locked = TrainerSessionSchedule::query()->with('memberTrainerPackage.trainer')->lockForUpdate()->findOrFail($schedule->id);
            $package = $locked->memberTrainerPackage;

            if ($role === 'member') {
                abort_unless((int) $package->user_id === (int) $request->user()->id, 403);
            } else {
                abort_unless((int) $package->trainer?->user_id === (int) $request->user()->id, 403);
            }

            abort_unless($locked->status === 'scheduled', 422, 'Jadwal ini tidak lagi menunggu konfirmasi.');
            $sessionEndsAt = $locked->session_date->toDateString().' '.$locked->end_time;
            abort_unless(now()->gte($sessionEndsAt), 422, 'Konfirmasi kehadiran hanya tersedia setelah jadwal sesi berakhir.');

            if ($role === 'member') {
                $locked->member_attended = true;
                $locked->member_confirmed_at = now();
            } else {
                $locked->trainer_attended = true;
                $locked->trainer_confirmed_at = now();
            }

            if ($locked->member_attended && $locked->trainer_attended) {
                $package = MemberTrainerPackage::query()->lockForUpdate()->findOrFail($package->id);
                if ($package->used_sessions >= $package->total_sessions) {
                    throw ValidationException::withMessages(['schedule' => 'Kuota sesi paket sudah habis.']);
                }

                $package->used_sessions++;
                if ($package->used_sessions >= $package->total_sessions) {
                    $package->status = 'completed';
                }
                $package->save();
                $locked->status = 'completed';
            }

            $locked->save();
        });
    }
}
