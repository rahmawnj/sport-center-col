<?php

namespace App\Http\Controllers;

use App\Models\MemberTrainerPackage;
use App\Models\Trainer;
use App\Models\TrainerPackage;
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

        $trainerSchedules = \\App\\Models\\TrainerSessionSchedule::query()
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
                'status' => $schedule->status,
                'can_confirm' => $schedule->status === 'scheduled' && now()->gte($schedule->session_date->toDateString().' '.$schedule->end_time),
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

        $assignedPackages = TrainerPackage::with('facility:id,name')
            ->whereHas('trainers', fn ($query) => $query->where('trainers.id', $trainer->id))
            ->orderBy('name')
            ->get()
            ->map(fn ($package) => [
                'id' => $package->id,
                'name' => $package->name,
                'sports' => $package->facility ? [$package->facility->name] : [],
                'total_sessions' => $package->total_sessions,
                'duration_days' => $package->duration_days,
                'price' => (float) $package->price,
                'is_active' => $package->is_active,
            ]);

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
            'assignedPackages' => $assignedPackages,
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
