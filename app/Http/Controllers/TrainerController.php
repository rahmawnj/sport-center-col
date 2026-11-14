<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Role;
use App\Models\Trainer;
use App\Models\TrainerSessionSchedule;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrainerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $items = Trainer::query()
            ->with(['facilities' => fn ($query) => $query->where('has_membership', true)->select('facilities.id', 'facilities.name'), 'user:id,name,email'])
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('specialty', 'like', "%{$search}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Trainer $trainer) => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'user_id' => $trainer->user_id,
                'user_name' => $trainer->user?->name,
                'user_email' => $trainer->user?->email,
                'specialty' => $trainer->specialty,
                'facility_ids' => $trainer->facilities->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'facilities' => $trainer->facilities->pluck('name')->all(),
            ]);

        return Inertia::render('trainers/Index', [
            'trainers' => $items,
            'facilities' => Facility::query()->where('status', 'active')->where('has_membership', true)->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search],
            'stats' => ['total' => Trainer::count()],
        ]);
    }

    public function show(Trainer $trainer): Response
    {
        $trainer->load(['facilities:id,name', 'user:id,name,email']);

        $memberPackages = $trainer->memberPackages()
            ->with(['user:id,name,email', 'trainerPackage:id,name,price', 'facility:id,name'])
            ->latest()
            ->get();

        $completedSessions = TrainerSessionSchedule::query()
            ->whereIn('member_trainer_package_id', $memberPackages->pluck('id'))
            ->where('status', 'completed')
            ->where('member_attended', true)
            ->where('trainer_attended', true)
            ->selectRaw('member_trainer_package_id, COUNT(*) as completed_count')
            ->groupBy('member_trainer_package_id')
            ->pluck('completed_count', 'member_trainer_package_id');

        $packageHistory = $memberPackages->map(function ($item) use ($completedSessions) {
            $completed = min(
                (int) ($completedSessions[$item->id] ?? 0),
                (int) $item->total_sessions,
            );
            $price = (float) ($item->trainerPackage?->price ?? 0);
            $percentage = (float) ($item->trainer_share_percentage ?? $item->trainerPackage?->trainer_share_percentage ?? 0);
            $maximumShare = $price * $percentage / 100;
            $earned = $item->total_sessions > 0
                ? $maximumShare * $completed / $item->total_sessions
                : 0;

            return [
                'id' => $item->id,
                'member_name' => $item->user?->name ?? 'Member',
                'package_name' => $item->trainerPackage?->name ?? 'Paket trainer',
                'facility_name' => $item->facility?->name ?? '—',
                'start_date' => $item->start_date?->toDateString(),
                'expires_at' => $item->expires_at?->toDateString(),
                'status' => $item->status,
                'total_sessions' => (int) $item->total_sessions,
                'completed_sessions' => $completed,
                'share_percentage' => $percentage,
                'earned_amount' => round($earned, 2),
            ];
        })->values();

        return Inertia::render('trainers/Show', [
            'trainer' => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'specialty' => $trainer->specialty,
                'created_at' => $trainer->created_at?->toISOString(),
                'updated_at' => $trainer->updated_at?->toISOString(),
                'user_name' => $trainer->user?->name,
                'user_email' => $trainer->user?->email,
                'facilities' => $trainer->facilities->pluck('name')->values(),
                'stats' => [
                    'packages_count' => $packageHistory->count(),
                    'completed_sessions' => $packageHistory->sum('completed_sessions'),
                    'earned_total' => round($packageHistory->sum('earned_amount'), 2),
                ],
                'packages' => $packageHistory,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'facility_ids' => ['required', 'array', 'min:1'],
            'facility_ids.*' => ['integer', 'distinct', Rule::exists('facilities', 'id')->where('has_membership', true)],
        ]);

        DB::transaction(function () use ($data) {
            $role = Role::where('name', 'Trainer')->firstOrFail();

            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role_id = $role->id;
            $user->save();

            $trainer = Trainer::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'specialty' => $data['specialty'] ?? null,
            ]);
            $trainer->facilities()->sync($data['facility_ids']);
        });

        return back()->with('success', 'Trainer berhasil dibuat.');
    }

    public function update(Request $request, Trainer $trainer): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($trainer->user_id)],
            'password' => [Rule::requiredIf(! $trainer->user_id), 'nullable', 'string', 'min:8'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'facility_ids' => ['required', 'array', 'min:1'],
            'facility_ids.*' => ['integer', 'distinct', 'exists:facilities,id'],
        ]);

        DB::transaction(function () use ($data, $trainer) {
            $role = Role::where('name', 'Trainer')->firstOrFail();
            $user = $trainer->user;

            if (! $user) {
                $user = new User();
                $user->password = $data['password'];
            } elseif (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->role_id = $role->id;
            $user->save();

            $trainer->update([
                'user_id' => $user->id,
                'name' => $data['name'],
                'specialty' => $data['specialty'] ?? null,
            ]);
            $trainer->facilities()->sync($data['facility_ids']);
        });

        return back()->with('success', 'Trainer berhasil diperbarui.');
    }

    public function destroy(Trainer $trainer): RedirectResponse
    {
        if ($trainer->memberPackages()->exists()) {
            return back()->with('error', 'Trainer sudah memiliki riwayat paket member dan tidak dapat dihapus.');
        }

        $trainer->facilities()->detach();
        $trainer->delete();

        return back()->with('success', 'Trainer berhasil dihapus.');
    }
}
