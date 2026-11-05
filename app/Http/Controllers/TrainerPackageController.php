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
            ->latest()
            ->paginate(10, ['*'], 'packages_page')
            ->withQueryString()
            ->with('trainers:id,name')
            ->through(fn (TrainerPackage $package) => [
                'id' => $package->id,
                'trainers' => $package->trainers->map(fn (Trainer $trainer) => ['id' => $trainer->id, 'name' => $trainer->name])->values(),
                'trainer_ids' => $package->trainers->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'name' => $package->name,
                'total_sessions' => $package->total_sessions,
                'duration_days' => $package->duration_days,
                'price' => (float) $package->price,
                'description' => $package->description,
                'is_active' => (bool) $package->is_active,
            ]);

        $members = User::query()->whereHas('memberProfile')->orderBy('name')->get(['id', 'name']);
        $trainers = Trainer::query()->with('facilities:id,name')->orderBy('name')->get(['id', 'name', 'specialty'])->map(fn (Trainer $trainer) => ['id' => $trainer->id, 'name' => $trainer->name, 'specialty' => $trainer->specialty, 'facility_ids' => $trainer->facilities->pluck('id')->map(fn ($id) => (int) $id)->all()]);
        $facilities = Facility::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        $memberPackages = MemberTrainerPackage::query()
            ->with(['user:id,name', 'trainer:id,name', 'trainerPackage:id,name', 'facility:id,name'])
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('trainerPackage', fn ($package) => $package->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('trainer', fn ($trainer) => $trainer->where('name', 'like', "%{$search}%"));
            }))
            ->when($status === 'active', fn ($query) => $query->where('status', 'active')->whereDate('expires_at', '>=', today())->whereColumn('used_sessions', '<', 'total_sessions'))
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
                'status' => $item->expires_at->lt(today()) && $item->status === 'active'
                    ? 'expired'
                    : ($item->used_sessions >= $item->total_sessions && $item->status === 'active' ? 'completed' : $item->status),
            ]);

        return Inertia::render('trainer-packages/Index', [
            'packages' => $packages,
            'packageOptions' => TrainerPackage::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'total_sessions', 'price']),
            'members' => $members,
            'trainers' => $trainers,
            'facilities' => $facilities,
            'memberPackages' => $memberPackages,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'packages' => TrainerPackage::count(),
                'active' => MemberTrainerPackage::where('status', 'active')->whereDate('expires_at', '>=', today())->whereColumn('used_sessions', '<', 'total_sessions')->count(),
                'expiring' => MemberTrainerPackage::where('status', 'active')->whereBetween('expires_at', [today(), today()->addDays(7)])->count(),
                'expired' => MemberTrainerPackage::where(function ($query) {
                    $query->where('status', 'expired')->orWhere(fn ($q) => $q->where('status', 'active')->whereDate('expires_at', '<', today()));
                })->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'total_sessions' => ['required', 'integer', 'min:1'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'trainer_ids' => ['required', 'array', 'min:1'],
            'trainer_ids.*' => ['required', 'integer', 'distinct', 'exists:trainers,id'],
        ]);

        $trainerIds = $data['trainer_ids'];
        unset($data['trainer_ids']);

        $package = TrainerPackage::create($data);
        $package->trainers()->sync($trainerIds);

        return back()->with('success', 'Paket trainer berhasil ditambahkan.');
    }

    public function update(Request $request, TrainerPackage $trainerPackage): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'total_sessions' => ['required', 'integer', 'min:1'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'trainer_ids' => ['required', 'array', 'min:1'],
            'trainer_ids.*' => ['required', 'integer', 'distinct', 'exists:trainers,id'],
        ]);

        $trainerIds = $data['trainer_ids'];
        unset($data['trainer_ids']);

        $trainerPackage->update($data);
        $trainerPackage->trainers()->sync($trainerIds);

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

            if ($item->status !== 'active' || $item->expires_at->lt(today())) {
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
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'payment_status' => ['required', Rule::in(['paid', 'pending'])],
        ]);

        $package = TrainerPackage::query()->where('is_active', true)->with('trainers')->findOrFail($data['trainer_package_id']);
        abort_unless($package->trainers->contains('id', (int) $data['trainer_id']), 422, 'Trainer tidak ditugaskan pada paket ini.');
        Trainer::query()->whereHas('facilities', fn ($query) => $query->where('facilities.id', $data['facility_id']))->findOrFail($data['trainer_id']);
        $start = Carbon::parse($data['start_date']);
        $expires = $start->copy()->addDays($package->duration_days - 1);
        $paid = $data['payment_status'] === 'paid';

        DB::transaction(function () use ($data, $package, $start, $expires, $paid) {
            $memberPackage = MemberTrainerPackage::create([
                'user_id' => $data['user_id'],
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
                'user_id' => $data['user_id'],
                'customer_type' => 'member',
                'transactionable_type' => MemberTrainerPackage::class,
                'transactionable_id' => $memberPackage->id,
                'customer_name' => User::find($data['user_id'])?->name,
                'amount' => $package->price,
                'payment_method' => $data['payment_method'],
                'payment_status' => $paid ? 'fully_paid' : 'unpaid',
            ]);
        });

        return back()->with('success', 'Pembelian paket personal trainer berhasil dicatat.');
    }
}
