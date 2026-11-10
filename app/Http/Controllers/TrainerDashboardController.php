<?php

namespace App\Http\Controllers;

use App\Models\MemberTrainerPackage;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainerDashboardController extends Controller
{
    private function trainerFor(Request $request): Trainer
    {
        return Trainer::where('user_id', $request->user()->id)->firstOrFail();
    }

    private function packageStatus(MemberTrainerPackage $package): string
    {
        if ($package->status === 'active' && $package->start_date->gt(today())) {
            return 'scheduled';
        }

        if ($package->status === 'active' && $package->expires_at->lt(today())) {
            return 'expired';
        }

        if ($package->status === 'active' && $package->used_sessions >= $package->total_sessions) {
            return 'completed';
        }

        return $package->status;
    }

    public function index(Request $request): Response
    {
        $trainer = $this->trainerFor($request);
        $packages = MemberTrainerPackage::with(['user:id,name,email', 'trainerPackage:id,name', 'facility:id,name'])
            ->where('trainer_id', $trainer->id)
            ->latest()
            ->get();

        $trainerSchedules = \App\Models\TrainerSessionSchedule::query()
            ->with(['memberTrainerPackage.user:id,name', 'memberTrainerPackage.trainerPackage:id,name'])
            ->whereHas('memberTrainerPackage', fn ($query) => $query->where('trainer_id', $trainer->id))
            ->latest('session_date')->latest('start_time')->limit(30)->get()
            ->map(fn ($schedule) => [
                'id' => $schedule->id,
                'member' => $schedule->memberTrainerPackage?->user?->name,
                'package' => $schedule->memberTrainerPackage?->trainerPackage?->name,
                'date' => $schedule->session_date?->toDateString(),
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'member_attended' => (bool) $schedule->member_attended,
                'trainer_attended' => (bool) $schedule->trainer_attended,
                'member_confirmed_at' => $schedule->member_confirmed_at?->toISOString(),
                'trainer_confirmed_at' => $schedule->trainer_confirmed_at?->toISOString(),
                'status' => $schedule->status,
                'can_confirm' => $schedule->status === 'scheduled',
            ]);

        return Inertia::render('trainer/Dashboard', [
            'trainerSchedules' => $trainerSchedules,
            'trainer' => [
                'name' => $trainer->name,
                'specialty' => $trainer->specialty,
                'facilities' => $trainer->facilities()->pluck('name')->all(),
            ],
            'stats' => [
                'total_members' => $packages->whereNotIn('status', ['cancelled'])->unique('user_id')->count(),
                'active_packages' => $packages->filter(fn ($package) => $package->isActive())->count(),
                'completed_sessions' => (int) $packages->sum('used_sessions'),
                'remaining_sessions' => (int) $packages->filter(fn ($package) => $package->isActive())
                    ->sum(fn ($package) => max(0, $package->total_sessions - $package->used_sessions)),
            ],
            'recent_members' => $packages->take(5)->map(fn ($package) => [
                'id' => $package->id,
                'member' => $package->user?->name ?? 'Member',
                'package' => $package->trainerPackage?->name ?? 'Paket Trainer',
                'facility' => $package->facility?->name,
                'used_sessions' => $package->used_sessions,
                'total_sessions' => $package->total_sessions,
                'status' => $this->packageStatus($package),
            ])->values(),
        ]);
    }

    public function members(Request $request): Response
    {
        $trainer = $this->trainerFor($request);

        $members = MemberTrainerPackage::with(['user:id,name,email', 'trainerPackage:id,name', 'facility:id,name'])
            ->where('trainer_id', $trainer->id)
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($package) => [
                'id' => $package->id,
                'member' => $package->user?->name ?? 'Member',
                'email' => $package->user?->email,
                'package' => $package->trainerPackage?->name ?? 'Paket Trainer',
                'facility' => $package->facility?->name,
                'start_date' => $package->start_date?->toDateString(),
                'expires_at' => $package->expires_at?->toDateString(),
                'used_sessions' => $package->used_sessions,
                'total_sessions' => $package->total_sessions,
                'remaining_sessions' => max(0, $package->total_sessions - $package->used_sessions),
                'status' => $this->packageStatus($package),
            ]);

        return Inertia::render('trainer/Members', [
            'members' => $members,
        ]);
    }


    public function memberShow(Request $request, MemberTrainerPackage $memberTrainerPackage): Response
    {
        $trainer = $this->trainerFor($request);

        abort_unless((int) $memberTrainerPackage->trainer_id === (int) $trainer->id, 404);

        $memberTrainerPackage->load([
            'user:id,name,email',
            'trainerPackage:id,name,total_sessions,duration_days,price',
            'facility:id,name',
        ]);

        $schedules = \App\Models\TrainerSessionSchedule::query()
            ->where('member_trainer_package_id', $memberTrainerPackage->id)
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->get()
            ->map(fn ($schedule) => [
                'id' => $schedule->id,
                'date' => $schedule->session_date?->toDateString(),
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'status' => $schedule->status,
            ]);

        return Inertia::render('trainer/MemberShow', [
            'item' => [
                'id' => $memberTrainerPackage->id,
                'member' => $memberTrainerPackage->user?->name ?? 'Member',
                'email' => $memberTrainerPackage->user?->email,
                'package' => $memberTrainerPackage->trainerPackage?->name ?? 'Paket Trainer',
                'facility' => $memberTrainerPackage->facility?->name,
                'start_date' => $memberTrainerPackage->start_date?->toDateString(),
                'expires_at' => $memberTrainerPackage->expires_at?->toDateString(),
                'total_sessions' => $memberTrainerPackage->total_sessions,
                'used_sessions' => $memberTrainerPackage->used_sessions,
                'remaining_sessions' => $memberTrainerPackage->remaining_sessions,
                'status' => $this->packageStatus($memberTrainerPackage),
            ],
            'schedules' => $schedules,
        ]);
    }

    public function profile(Request $request): Response
    {
        $trainer = $this->trainerFor($request)->load(['user:id,name,email', 'facilities:id,name']);

        return Inertia::render('trainer/Profile', [
            'profile' => [
                'name' => $trainer->name,
                'email' => $trainer->user?->email,
                'phone' => $trainer->phone,
                'specialty' => $trainer->specialty,
                'facilities' => $trainer->facilities->pluck('name')->all(),
            ],
        ]);
    }
}
