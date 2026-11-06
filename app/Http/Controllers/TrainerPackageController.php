<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\MemberTrainerPackage;
use App\Models\Trainer;
use App\Models\TrainerPackage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrainerPackageController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');

        $packages = TrainerPackage::query()
            ->whereHas('facility', fn ($query) => $query->where('has_membership', true))
            ->with('facility:id,name')
            ->latest()
            ->paginate(10, ['*'], 'packages_page')
            ->withQueryString()
            ->through(fn (TrainerPackage $package) => [
                'id' => $package->id,
                'facility' => $package->facility ? ['id' => $package->facility->id, 'name' => $package->facility->name] : null,
                'facility_id' => $package->facility_id,
                'name' => $package->name,
                'total_sessions' => $package->total_sessions,
                'duration_days' => $package->duration_days,
                'price' => (float) $package->price,
                'description' => $package->description,
                'is_active' => (bool) $package->is_active,
            ]);

        $members = User::query()->whereHas('memberProfile')->orderBy('name')->get(['id', 'name']);
        $trainers = Trainer::query()->with(['facilities' => fn ($query) => $query->where('has_membership', true)->select('facilities.id', 'facilities.name')])->orderBy('name')->get(['id', 'name', 'specialty'])->map(fn (Trainer $trainer) => ['id' => $trainer->id, 'name' => $trainer->name, 'specialty' => $trainer->specialty, 'facility_ids' => $trainer->facilities->pluck('id')->map(fn ($id) => (int) $id)->all()]);
        $facilities = Facility::query()->where('status', 'active')->where('has_membership', true)->orderBy('name')->get(['id', 'name']);

        $memberPackages = MemberTrainerPackage::query()
            ->with(['user:id,name', 'trainer:id,name', 'trainerPackage:id,name', 'facility:id,name'])
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('trainerPackage', fn ($package) => $package->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('trainer', fn ($trainer) => $trainer->where('name', 'like', "%{$search}%"));
            }))
            ->when($status === 'active', fn ($query) => $query->where('status', 'active')->whereDate('start_date', '<=', today())->whereDate('expires_at', '>=', today())->whereColumn('used_sessions', '<', 'total_sessions'))
            ->when($status === 'scheduled', fn ($query) => $query->where('status', 'active')->whereDate('start_date', '>', today()))
            ->when($status === 'pending', fn ($query) => $query->where('status', 'pending'))
            ->when($status === 'completed', fn ($query) => $query->where(fn ($q) => $q->where('status', 'completed')->orWhere(fn ($active) => $active->where('status', 'active')->whereColumn('used_sessions', '>=', 'total_sessions'))))
            ->when($status === 'expired', fn ($query) => $query->where(function ($q) {
                $q->where('status', 'expired')->orWhere(fn ($active) => $active->where('status', 'active')->whereDate('expires_at', '<', today()));
            }))
            ->when($status === 'cancelled', fn ($query) => $query->where('status', 'cancelled'))
            ->latest()
            ->paginate(10, ['*'], 'memberships_page')
            ->withQueryString()
            ->through(fn (MemberTrainerPackage $item) => [
                'id' => $item->id,
                'member_name' => $item->user?->name,
                'trainer_name' => $item->trainer?->name,
                'facility_name' => $item->facility?->name,
                'package_name' => $item->trainerPackage?->name,
                'start_date' => $item->start_date?->toDateString(),
                'expires_at' => $item->expires_at?->toDateString(),
                'total_sessions' => $item->total_sessions,
                'used_sessions' => $item->used_sessions,
                'remaining_sessions' => $item->remaining_sessions,
                'status' => $item->status === 'active' && $item->start_date->gt(today())
                    ? 'scheduled'
                    : ($item->expires_at->lt(today()) && $item->status === 'active'
                        ? 'expired'
                        : ($item->used_sessions >= $item->total_sessions && $item->status === 'active' ? 'completed' : $item->status)),
            ]);

        return Inertia::render('trainer-packages/Index', [
            'packages' => $packages,
            'packageOptions' => TrainerPackage::query()->where('is_active', true)->whereHas('facility', fn ($query) => $query->where('has_membership', true))->orderBy('name')->get(['id', 'facility_id', 'name', 'total_sessions', 'price'])->map(fn (TrainerPackage $package) => ['id' => $package->id, 'name' => $package->name, 'total_sessions' => $package->total_sessions, 'price' => (float) $package->price, 'facility_id' => $package->facility_id]),
            'members' => $members,
            'trainers' => $trainers,
            'facilities' => $facilities,
            'memberPackages' => $memberPackages,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'packages' => TrainerPackage::count(),
                'active' => MemberTrainerPackage::where('status', 'active')->whereDate('start_date', '<=', today())->whereDate('expires_at', '>=', today())->whereColumn('used_sessions', '<', 'total_sessions')->count(),
                'expiring' => MemberTrainerPackage::where('status', 'active')->whereDate('start_date', '<=', today())->whereBetween('expires_at', [today(), today()->addDays(7)])->count(),
                'expired' => MemberTrainerPackage::where(function ($query) {
                    $query->where('status', 'expired')->orWhere(fn ($q) => $q->where('status', 'active')->whereDate('expires_at', '<', today()));
                })->count(),
            ],
        ]);
    }


    public function membershipTrainers(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $activeTrainers = MemberTrainerPackage::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('expires_at', '>=', today())
            ->whereColumn('used_sessions', '<', 'total_sessions');

        $rows = (clone $activeTrainers)
            ->with([
                'user:id,name',
                'user.userMemberships' => fn ($query) => $query->with('package:id,name,facility_id'),
                'trainer:id,name',
                'trainerPackage:id,name',
                'facility:id,name',
            ])
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('trainer', fn ($trainer) => $trainer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('trainerPackage', fn ($package) => $package->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('facility', fn ($facility) => $facility->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('expires_at')
            ->paginate(15)
            ->withQueryString()
            ->through(function (MemberTrainerPackage $item) {
                $membership = $item->user?->userMemberships
                    ->filter(fn ($record) => (int) $record->package?->facility_id === (int) $item->facility_id)
                    ->sortByDesc(fn ($record) => $record->end_date?->timestamp ?? 0)
                    ->first();

                $membershipStatus = 'none';
                if ($membership) {
                    if (
                        $membership->status === 'active'
                        && $membership->start_date?->lte(today())
                        && $membership->end_date?->gte(today())
                    ) {
                        $membershipStatus = 'active';
                    } elseif (
                        $membership->status === 'active'
                        && $membership->start_date?->gt(today())
                    ) {
                        $membershipStatus = 'upcoming';
                    } else {
                        $membershipStatus = 'expired';
                    }
                }

                return [
                    'id' => $item->id,
                    'member_name' => $item->user?->name,
                    'trainer_name' => $item->trainer?->name,
                    'trainer_package' => $item->trainerPackage?->name,
                    'facility_name' => $item->facility?->name,
                    'trainer_start' => $item->start_date?->toDateString(),
                    'trainer_end' => $item->expires_at?->toDateString(),
                    'remaining_sessions' => $item->remaining_sessions,
                    'total_sessions' => $item->total_sessions,
                    'membership_package' => $membership?->package?->name,
                    'membership_end' => $membership?->end_date?->toDateString(),
                    'membership_status' => $membershipStatus,
                ];
            });

        $withoutMembership = (clone $activeTrainers)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('user_memberships')
                    ->join('packages', 'packages.id', '=', 'user_memberships.package_id')
                    ->whereColumn('user_memberships.user_id', 'member_trainer_packages.user_id')
                    ->whereColumn('packages.facility_id', 'member_trainer_packages.facility_id')
                    ->where('user_memberships.status', 'active')
                    ->whereDate('user_memberships.start_date', '<=', today())
                    ->whereDate('user_memberships.end_date', '>=', today());
            })
            ->count();

        $schedulePackages = (clone $activeTrainers)
            ->with(['user:id,name', 'trainer:id,name', 'trainerPackage:id,name', 'facility:id,name'])
            ->get()
            ->map(fn (MemberTrainerPackage $item) => [
                'id' => $item->id,
                'label' => ($item->user?->name ?? 'Member').' — '.($item->trainer?->name ?? 'Trainer').' — '.($item->trainerPackage?->name ?? 'Paket').' ('.$item->remaining_sessions.' sesi)',
            ])
            ->values();

        $sessionSchedules = \\App\\Models\\TrainerSessionSchedule::query()
            ->with(['memberTrainerPackage.user:id,name', 'memberTrainerPackage.trainer:id,name', 'memberTrainerPackage.trainerPackage:id,name'])
            ->latest('session_date')
            ->latest('start_time')
            ->limit(100)
            ->get()
            ->map(fn ($schedule) => [
                'id' => $schedule->id,
                'member' => $schedule->memberTrainerPackage?->user?->name,
                'trainer' => $schedule->memberTrainerPackage?->trainer?->name,
                'package' => $schedule->memberTrainerPackage?->trainerPackage?->name,
                'date' => $schedule->session_date?->toDateString(),
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'member_attended' => (bool) $schedule->member_attended,
                'trainer_attended' => (bool) $schedule->trainer_attended,
                'status' => $schedule->status,
            ]);

        return Inertia::render('membership-trainers/Index', [
            'rows' => $rows,
            'schedulePackages' => $schedulePackages,
            'sessionSchedules' => $sessionSchedules,
            'filters' => ['search' => $search],
            'stats' => [
                'active_trainers' => (clone $activeTrainers)->count(),
                'membership_ended' => $withoutMembership,
                'membership_active' => (clone $activeTrainers)->count() - $withoutMembership,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'facility_id' => ['required', 'integer', Rule::exists('facilities', 'id')->where('has_membership', true)],
            'total_sessions' => ['required', 'integer', 'min:1'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        TrainerPackage::create($data);

        return back()->with('success', 'Paket trainer berhasil ditambahkan.');
    }

    public function update(Request $request, TrainerPackage $trainerPackage): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'facility_id' => ['required', 'integer', Rule::exists('facilities', 'id')->where('has_membership', true)->where('status', 'active')],
            'total_sessions' => ['required', 'integer', 'min:1'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $trainerPackage->update($data);

        return back()->with('success', 'Paket trainer berhasil diperbarui.');
    }

    public function destroy(TrainerPackage $trainerPackage): RedirectResponse
    {
        if ($trainerPackage->memberPackages()->exists()) {
            return back()->with('error', 'Paket sudah pernah dibeli dan tidak dapat dihapus. Nonaktifkan paket saja.');
        }

        $trainerPackage->delete();

        return back()->with('success', 'Paket trainer berhasil dihapus.');
    }

    public function useSession(MemberTrainerPackage $memberTrainerPackage): RedirectResponse
    {
        DB::transaction(function () use ($memberTrainerPackage) {
            $item = MemberTrainerPackage::query()->lockForUpdate()->findOrFail($memberTrainerPackage->id);

            if ($item->status !== 'active' || $item->start_date->gt(today()) || $item->expires_at->lt(today())) {
                abort(422, 'Paket tidak aktif atau sudah expired.');
            }

            if ($item->used_sessions >= $item->total_sessions) {
                abort(422, 'Semua sesi paket sudah digunakan.');
            }

            $item->used_sessions++;
            if ($item->used_sessions >= $item->total_sessions) {
                $item->status = 'completed';
            }
            $item->save();
        });

        return back()->with('success', 'Satu sesi personal trainer berhasil digunakan.');
    }

    public function purchase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('member_profiles', 'user_id')],
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'trainer_id' => ['required', 'integer', 'exists:trainers,id'],
            'trainer_package_id' => ['required', 'integer', 'exists:trainer_packages,id'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'payment_status' => ['required', Rule::in(['paid', 'pending'])],
        ]);

        $package = TrainerPackage::query()->where('is_active', true)->whereHas('facility', fn ($query) => $query->where('has_membership', true))->findOrFail($data['trainer_package_id']);
        abort_unless((int) $package->facility_id === (int) $data['facility_id'], 422, 'Olahraga tidak sesuai dengan paket yang dipilih.');
        Trainer::query()->whereHas('facilities', fn ($query) => $query->where('facilities.id', $data['facility_id']))->findOrFail($data['trainer_id']);
        $paid = $data['payment_status'] === 'paid';

        DB::transaction(function () use ($data, $package, $paid) {
            $member = User::query()->lockForUpdate()->findOrFail($data['user_id']);

            $latestPackage = MemberTrainerPackage::query()
                ->where('user_id', $member->id)
                ->where('facility_id', $data['facility_id'])
                ->where('status', 'active')
                ->whereColumn('used_sessions', '<', 'total_sessions')
                ->whereDate('expires_at', '>=', today())
                ->orderByDesc('expires_at')
                ->lockForUpdate()
                ->first();

            $start = $latestPackage
                ? $latestPackage->expires_at->copy()->addDay()
                : today();
            $expires = $start->copy()->addDays(max(1, (int) $package->duration_days) - 1);

            $memberPackage = MemberTrainerPackage::create([
                'user_id' => $member->id,
                'trainer_package_id' => $package->id,
                'trainer_id' => $data['trainer_id'],
                'facility_id' => $data['facility_id'],
                'start_date' => $start->toDateString(),
                'expires_at' => $expires->toDateString(),
                'total_sessions' => $package->total_sessions,
                'used_sessions' => 0,
                'status' => $paid ? 'active' : 'pending',
            ]);

            Transaction::create([
                'user_id' => $member->id,
                'transactionable_type' => 'membership',
                'customer_name' => $member->name,
                'amount' => $package->price,
                'payment_method' => $data['payment_method'],
                'payment_status' => $paid ? 'fully_paid' : 'unpaid',
            ]);
        });

        return back()->with('success', 'Pembelian paket personal trainer berhasil dicatat.');
    }
}
