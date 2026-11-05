<?php

namespace App\Http\Controllers;

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
    public function index(): Response
    {
        $packages = TrainerPackage::query()->latest()->get();
        $members = User::query()->whereHas('memberProfile')->orderBy('name')->get(['id', 'name']);
        $trainers = Trainer::query()->orderBy('name')->get(['id', 'name', 'specialty']);

        $memberPackages = MemberTrainerPackage::query()
            ->with(['user:id,name', 'trainer:id,name', 'trainerPackage:id,name'])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (MemberTrainerPackage $item) => [
                'id' => $item->id,
                'member_name' => $item->user?->name,
                'trainer_name' => $item->trainer?->name,
                'package_name' => $item->trainerPackage?->name,
                'start_date' => $item->start_date?->toDateString(),
                'expires_at' => $item->expires_at?->toDateString(),
                'total_sessions' => $item->total_sessions,
                'used_sessions' => $item->used_sessions,
                'remaining_sessions' => $item->remaining_sessions,
                'status' => $item->expires_at->lt(today()) && $item->status === 'active'
                    ? 'expired' : $item->status,
            ]);

        return Inertia::render('trainer-packages/Index', [
            'packages' => $packages,
            'members' => $members,
            'trainers' => $trainers,
            'memberPackages' => $memberPackages,
            'stats' => [
                'packages' => TrainerPackage::count(),
                'active' => MemberTrainerPackage::where('status', 'active')->whereDate('expires_at', '>=', today())->count(),
                'expiring' => MemberTrainerPackage::where('status', 'active')->whereBetween('expires_at', [today(), today()->addDays(7)])->count(),
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
        ]);

        TrainerPackage::create($data);

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
            'trainer_id' => ['required', 'integer', 'exists:trainers,id'],
            'trainer_package_id' => ['required', 'integer', 'exists:trainer_packages,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'payment_status' => ['required', Rule::in(['paid', 'pending'])],
        ]);

        $package = TrainerPackage::query()->where('is_active', true)->findOrFail($data['trainer_package_id']);
        $start = Carbon::parse($data['start_date']);
        $expires = $start->copy()->addDays($package->duration_days - 1);
        $paid = $data['payment_status'] === 'paid';

        DB::transaction(function () use ($data, $package, $start, $expires, $paid) {
            $memberPackage = MemberTrainerPackage::create([
                'user_id' => $data['user_id'],
                'trainer_package_id' => $package->id,
                'trainer_id' => $data['trainer_id'],
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
