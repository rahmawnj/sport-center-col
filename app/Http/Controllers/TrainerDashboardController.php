<?php

namespace App\Http\Controllers;

use App\Models\MemberTrainerPackage;
use App\Models\Trainer;
use App\Models\TrainerSessionSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrainerDashboardController extends Controller
{
    private function trainerFor(Request $request): Trainer
    {
        return Trainer::query()->where('user_id', $request->user()->id)->firstOrFail();
    }

    private function packageStatus(MemberTrainerPackage $package): string
    {
        if ($package->status === 'active' && $package->start_date?->gt(today())) return 'scheduled';
        if ($package->status === 'active' && $package->expires_at?->lt(today())) return 'expired';
        if ($package->status === 'active' && $package->used_sessions >= $package->total_sessions) return 'completed';

        return $package->status;
    }

    private function trainerEarnings($packages): array
    {
        $completedByPackage = TrainerSessionSchedule::query()
            ->whereIn('member_trainer_package_id', $packages->pluck('id'))
            ->where('status', 'completed')
            ->selectRaw('member_trainer_package_id, COUNT(*) as completed_count')
            ->groupBy('member_trainer_package_id')
            ->pluck('completed_count', 'member_trainer_package_id');

        $totalEarned = 0;
        $totalPotential = 0;

        foreach ($packages as $package) {
            $price = (float) ($package->trainerPackage?->price ?? 0);
            $percentage = (float) ($package->trainer_share_percentage ?? $package->trainerPackage?->trainer_share_percentage ?? 0);
            $maximumShare = $price * $percentage / 100;
            $completed = min((int) ($completedByPackage[$package->id] ?? 0), (int) $package->total_sessions);
            $earned = $package->total_sessions > 0
                ? $maximumShare * $completed / $package->total_sessions
                : 0;

            $totalEarned += $earned;
            $totalPotential += $maximumShare;
        }

        return [
            'earned' => round($totalEarned, 2),
            'potential' => round($totalPotential, 2),
        ];
    }

    public function index(Request $request): Response
    {
        $trainer = $this->trainerFor($request)->load('user:id,image_profile');
        $packages = MemberTrainerPackage::query()
            ->with(['user:id,name,email', 'trainerPackage:id,name,price,trainer_share_percentage', 'facility:id,name'])
            ->where('trainer_id', $trainer->id)
            ->latest()
            ->get();

        $scheduleQuery = TrainerSessionSchedule::query()
            ->with(['memberTrainerPackage.user:id,name', 'memberTrainerPackage.trainerPackage:id,name'])
            ->whereHas('memberTrainerPackage', fn ($query) => $query->where('trainer_id', $trainer->id));

        $todaySchedules = (clone $scheduleQuery)->whereDate('session_date', today())->where('status', 'scheduled')->count();
        $upcomingSchedules = (clone $scheduleQuery)->whereDate('session_date', '>=', today())->where('status', 'scheduled')->count();
        $completedSessions = (clone $scheduleQuery)->where('status', 'completed')->count();

        $scheduleItems = (clone $scheduleQuery)
            ->where('status', 'scheduled')
            ->whereDate('session_date', '>=', today())
            ->orderBy('session_date')->orderBy('start_time')->limit(6)->get()
            ->map(fn ($schedule) => [
                'id' => $schedule->id,
                'member' => $schedule->memberTrainerPackage?->user?->name ?? 'Member',
                'package' => $schedule->memberTrainerPackage?->trainerPackage?->name ?? 'Paket trainer',
                'date' => $schedule->session_date?->toDateString(),
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'member_confirmed_at' => $schedule->member_confirmed_at?->toISOString(),
                'trainer_confirmed_at' => $schedule->trainer_confirmed_at?->toISOString(),
            ])->values();

        $earnings = $this->trainerEarnings($packages);
        $activePackages = $packages->filter(fn ($package) => $package->isActive());

        return Inertia::render('trainer/Dashboard', [
            'trainer' => [
                'name' => $trainer->name,
                'specialty' => $trainer->specialty,
                'photo_url' => $trainer->user?->image_profile ? Storage::disk('public')->url($trainer->user->image_profile) : null,
                'facilities' => $trainer->facilities()->pluck('name')->all(),
            ],
            'stats' => [
                'total_members' => $packages->whereNotIn('status', ['cancelled'])->unique('user_id')->count(),
                'active_members' => $activePackages->unique('user_id')->count(),
                'today_schedules' => $todaySchedules,
                'upcoming_schedules' => $upcomingSchedules,
                'completed_sessions' => $completedSessions,
                'remaining_sessions' => (int) $activePackages->sum(fn ($package) => max(0, $package->total_sessions - $package->used_sessions)),
                'earned_income' => $earnings['earned'],
                'potential_income' => $earnings['potential'],
            ],
            'upcoming_schedules' => $scheduleItems,
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

    public function schedules(Request $request): Response
    {
        $trainer = $this->trainerFor($request);
        $validated = $request->validate([
            'status' => ['nullable', 'in:scheduled,completed,failed'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $status = $validated['status'] ?? '';
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $baseQuery = TrainerSessionSchedule::query()
            ->whereHas('memberTrainerPackage', fn ($query) => $query->where('trainer_id', $trainer->id))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($from, fn ($query) => $query->whereDate('session_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('session_date', '<=', $to));

        $schedules = TrainerSessionSchedule::query()
            ->with([
                'memberTrainerPackage.user:id,name,email',
                'memberTrainerPackage.trainerPackage:id,name',
                'memberTrainerPackage.facility:id,name',
            ])
            ->whereHas('memberTrainerPackage', fn ($query) => $query->where('trainer_id', $trainer->id))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($from, fn ($query) => $query->whereDate('session_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('session_date', '<=', $to))
            ->orderByRaw('CASE WHEN session_date >= ? THEN 0 ELSE 1 END', [today()->toDateString()])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($schedule) => [
                'id' => $schedule->id,
                'member' => $schedule->memberTrainerPackage?->user?->name ?? 'Member',
                'email' => $schedule->memberTrainerPackage?->user?->email,
                'package' => $schedule->memberTrainerPackage?->trainerPackage?->name ?? 'Paket trainer',
                'facility' => $schedule->memberTrainerPackage?->facility?->name,
                'date' => $schedule->session_date?->toDateString(),
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'member_confirmed_at' => $schedule->member_confirmed_at?->toISOString(),
                'trainer_confirmed_at' => $schedule->trainer_confirmed_at?->toISOString(),
                'member_attended' => (bool) $schedule->member_attended,
                'trainer_attended' => (bool) $schedule->trainer_attended,
                'status' => $schedule->status,
            ]);

        return Inertia::render('trainer/Schedules', [
            'schedules' => $schedules,
            'filters' => ['status' => $status, 'from' => $from, 'to' => $to],
            'summary' => [
                'today' => (clone $baseQuery)->whereDate('session_date', today())->where('status', 'scheduled')->count(),
                'upcoming' => (clone $baseQuery)->whereDate('session_date', '>=', today())->where('status', 'scheduled')->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            ],
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

        return Inertia::render('trainer/Members', ['members' => $members]);
    }

    public function memberShow(Request $request, MemberTrainerPackage $memberTrainerPackage): Response
    {
        $trainer = $this->trainerFor($request);
        abort_unless((int) $memberTrainerPackage->trainer_id === (int) $trainer->id, 404);

        $memberTrainerPackage->load([
            'user:id,name,email',
            'trainerPackage:id,name,total_sessions,duration_days,price,trainer_share_percentage',
            'facility:id,name',
        ]);

        $schedules = TrainerSessionSchedule::query()
            ->where('member_trainer_package_id', $memberTrainerPackage->id)
            ->orderByDesc('session_date')->orderByDesc('start_time')->get();

        $completed = min($schedules->where('status', 'completed')->count(), (int) $memberTrainerPackage->total_sessions);
        $price = (float) ($memberTrainerPackage->trainerPackage?->price ?? 0);
        $percentage = (float) ($memberTrainerPackage->trainer_share_percentage ?? $memberTrainerPackage->trainerPackage?->trainer_share_percentage ?? 0);
        $maximumShare = $price * $percentage / 100;
        $earned = $memberTrainerPackage->total_sessions > 0
            ? $maximumShare * $completed / $memberTrainerPackage->total_sessions
            : 0;

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
            'earnings' => [
                'package_price' => $price,
                'share_percentage' => $percentage,
                'completed_sessions' => $completed,
                'maximum_share' => round($maximumShare, 2),
                'earned_amount' => round($earned, 2),
            ],
            'schedules' => $schedules->map(fn ($schedule) => [
                'id' => $schedule->id,
                'date' => $schedule->session_date?->toDateString(),
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'status' => $schedule->status,
            ])->values(),
        ]);
    }

    public function profile(Request $request): Response
    {
        $trainer = $this->trainerFor($request)->load(['user:id,name,email', 'facilities:id,name']);

        return Inertia::render('trainer/Profile', [
            'profile' => [
                'name' => $trainer->name,
                'email' => $trainer->user?->email,
                'specialty' => $trainer->specialty,
                'photo_url' => $trainer->user?->image_profile ? Storage::disk('public')->url($trainer->user->image_profile) : null,
                'facilities' => $trainer->facilities->pluck('name')->all(),
            ],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $trainer = $this->trainerFor($request);
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'specialty' => ['nullable', 'string', 'max:255'],
            'image_profile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image_profile')) {
            if ($user->image_profile) {
                Storage::disk('public')->delete($user->image_profile);
            }
            $user->image_profile = $request->file('image_profile')->store('profile-photos', 'public');
        }

        $trainer->name = $data['name'];
        $trainer->specialty = $data['specialty'] ?? null;
        $trainer->save();

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
