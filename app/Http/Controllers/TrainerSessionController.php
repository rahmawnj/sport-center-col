<?php

namespace App\Http\Controllers;

use App\Models\MemberTrainerPackage;
use App\Models\TrainerSessionSchedule;
use App\Models\UserMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainerSessionController extends Controller
{
    public function store(Request $request, MemberTrainerPackage $memberTrainerPackage): RedirectResponse
    {
        $data = $request->validate([
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        DB::transaction(function () use ($data, $memberTrainerPackage) {
            $package = MemberTrainerPackage::query()->lockForUpdate()->findOrFail($memberTrainerPackage->id);
            abort_unless($package->isActive(), 422, 'Paket trainer tidak sedang aktif atau kuotanya habis.');

            $this->ensureMembershipCoversDate($package, $data['session_date']);
            $this->ensureNoScheduleOverlap($package, $data);

            TrainerSessionSchedule::create([
                'member_trainer_package_id' => $package->id,
                ...$data,
                'status' => 'scheduled',
                'member_attended' => false,
                'trainer_attended' => false,
            ]);
        });

        return back()->with('success', 'Jadwal sesi berhasil dibuat.');
    }

    public function update(Request $request, TrainerSessionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        DB::transaction(function () use ($data, $schedule) {
            $locked = TrainerSessionSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            abort_unless($locked->status === 'scheduled', 422, 'Hanya jadwal yang belum ditutup yang bisa diedit.');

            $package = MemberTrainerPackage::query()->lockForUpdate()->findOrFail($locked->member_trainer_package_id);
            abort_unless(
                $data['session_date'] >= $package->start_date->toDateString()
                && $data['session_date'] <= $package->expires_at->toDateString(),
                422,
                'Tanggal sesi harus berada dalam periode paket trainer.'
            );

            $this->ensureMembershipCoversDate($package, $data['session_date']);
            $this->ensureNoScheduleOverlap($package, $data, $locked->id);

            $locked->update([
                ...$data,
                'member_attended' => false,
                'trainer_attended' => false,
                'member_confirmed_at' => null,
                'trainer_confirmed_at' => null,
            ]);
        });

        return back()->with('success', 'Jadwal sesi berhasil diperbarui.');
    }

    public function confirmMember(Request $request, TrainerSessionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate(['attended' => ['required', 'boolean']]);
        $package = $schedule->memberTrainerPackage;
        abort_unless((int) $package->user_id === (int) $request->user()->id, 404);
        $this->recordAttendanceConfirmation($schedule, 'member', (bool) $data['attended']);

        return back()->with('success', 'Konfirmasi kehadiran member berhasil disimpan.');
    }

    public function confirmTrainer(Request $request, TrainerSessionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate(['attended' => ['required', 'boolean']]);
        $trainer = \App\Models\Trainer::query()->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless((int) $schedule->memberTrainerPackage?->trainer_id === (int) $trainer->id, 404);
        $this->recordAttendanceConfirmation($schedule, 'trainer', (bool) $data['attended']);

        return back()->with('success', 'Konfirmasi kehadiran trainer berhasil disimpan.');
    }

    private function recordAttendanceConfirmation(TrainerSessionSchedule $schedule, string $role, bool $attended): void
    {
        DB::transaction(function () use ($schedule, $role, $attended) {
            $locked = TrainerSessionSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            abort_unless($locked->status === 'scheduled', 422, 'Konfirmasi hanya bisa dilakukan untuk jadwal yang belum ditutup.');

            $timestamp = $role === 'member' ? 'member_confirmed_at' : 'trainer_confirmed_at';
            $attendance = $role === 'member' ? 'member_attended' : 'trainer_attended';
            abort_unless(! $locked->{$timestamp}, 422, 'Kehadiran sudah dikonfirmasi.');

            $locked->forceFill([
                $timestamp => now(),
                $attendance => $attended,
            ])->save();
        });
    }

    public function setResult(Request $request, TrainerSessionSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'result' => ['required', 'in:completed,failed'],
        ]);

        DB::transaction(function () use ($data, $schedule) {
            $locked = TrainerSessionSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            abort_unless($locked->status === 'scheduled', 422, 'Hasil sesi ini sudah ditetapkan sebelumnya.');

            $sessionEndsAt = $locked->session_date->toDateString().' '.$locked->end_time;
            abort_unless(now()->gte($sessionEndsAt), 422, 'Hasil sesi hanya bisa ditetapkan setelah jadwal berakhir.');

            $package = MemberTrainerPackage::query()
                ->lockForUpdate()
                ->findOrFail($locked->member_trainer_package_id);

            if ($data['result'] === 'completed') {
                if (! $locked->member_confirmed_at || ! $locked->trainer_confirmed_at
                    || ! $locked->member_attended || ! $locked->trainer_attended) {
                    throw ValidationException::withMessages([
                        'result' => 'Sesi hanya bisa ditandai berhasil setelah member dan trainer sama-sama mengonfirmasi hadir.',
                    ]);
                }

                if ($package->used_sessions >= $package->total_sessions) {
                    throw ValidationException::withMessages([
                        'result' => 'Kuota sesi paket sudah habis sehingga sesi tidak dapat ditandai berhasil.',
                    ]);
                }

                $package->used_sessions++;
                if ($package->used_sessions >= $package->total_sessions) {
                    $package->status = 'completed';
                }
                $package->save();
            }

            $locked->status = $data['result'];
            $locked->save();
        });

        return back()->with(
            'success',
            $data['result'] === 'completed'
                ? 'Sesi ditandai berhasil. Sisa sesi telah dikurangi satu.'
                : 'Sesi ditandai tidak berhasil. Sisa sesi tidak berubah.'
        );
    }

    private function ensureMembershipCoversDate(MemberTrainerPackage $package, string $date): void
    {
        $membershipActive = UserMembership::query()
            ->where('user_id', $package->user_id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereHas('package', fn ($query) => $query->where('facility_id', $package->facility_id))
            ->exists();

        abort_unless(
            $membershipActive,
            422,
            'Member harus memiliki membership aktif pada fasilitas yang sama hingga tanggal sesi.'
        );
    }

    private function ensureNoScheduleOverlap(MemberTrainerPackage $package, array $data, ?int $ignoreId = null): void
    {
        $overlap = TrainerSessionSchedule::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('session_date', $data['session_date'])
            ->whereIn('status', ['scheduled', 'completed'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->whereHas('memberTrainerPackage', fn ($query) => $query->where(function ($sub) use ($package) {
                $sub->where('trainer_id', $package->trainer_id)
                    ->orWhere('user_id', $package->user_id);
            }))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_time' => 'Jadwal bertabrakan dengan sesi member atau trainer lain.',
            ]);
        }
    }
}
