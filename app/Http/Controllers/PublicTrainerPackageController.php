<?php

namespace App\Http\Controllers;

use App\Models\MemberTrainerPackage;
use App\Models\TrainerPackage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicTrainerPackageController extends Controller
{
    public function show(Request $request, TrainerPackage $trainerPackage): Response
    {
        abort_unless($trainerPackage->is_active, 404);
        $trainerPackage->load(['trainers:id,name,specialty', 'facilities:id,name']);

        return Inertia::render('trainer-packages/Show', [
            'package' => [
                'id' => $trainerPackage->id,
                'name' => $trainerPackage->name,
                'description' => $trainerPackage->description,
                'total_sessions' => $trainerPackage->total_sessions,
                'duration_days' => $trainerPackage->duration_days,
                'price' => (float) $trainerPackage->price,
                'trainers' => $trainerPackage->trainers->map(fn ($trainer) => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'specialty' => $trainer->specialty,
                ])->values(),
                'facilities' => $trainerPackage->facilities->map(fn ($facility) => [
                    'id' => $facility->id,
                    'name' => $facility->name,
                ])->values(),
            ],
            'canPurchase' => $request->user()?->role?->name === 'Member',
        ]);
    }

    public function purchase(Request $request, TrainerPackage $trainerPackage): RedirectResponse
    {
        abort_unless($trainerPackage->is_active, 404);
        $data = $request->validate([
            'trainer_id' => ['required', 'integer', 'exists:trainers,id'],
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
        ]);

        $trainerPackage->load(['trainers:id', 'facilities:id']);
        abort_unless($trainerPackage->trainers->contains('id', (int) $data['trainer_id']), 422, 'Trainer tidak tersedia untuk paket ini.');
        abort_unless($trainerPackage->facilities->contains('id', (int) $data['facility_id']), 422, 'Olahraga tidak sesuai dengan paket ini.');

        DB::transaction(function () use ($request, $trainerPackage, $data) {
            $member = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $latestPackage = MemberTrainerPackage::query()
                ->where('user_id', $member->id)
                ->where('facility_id', $data['facility_id'])
                ->where('status', 'active')
                ->whereColumn('used_sessions', '<', 'total_sessions')
                ->whereDate('expires_at', '>=', today())
                ->orderByDesc('expires_at')
                ->lockForUpdate()
                ->first();

            $start = $latestPackage ? $latestPackage->expires_at->copy()->addDay() : today();
            $expires = $start->copy()->addDays(max(1, (int) $trainerPackage->duration_days) - 1);

            $memberPackage = MemberTrainerPackage::create([
                'user_id' => $member->id,
                'trainer_package_id' => $trainerPackage->id,
                'trainer_id' => $data['trainer_id'],
                'facility_id' => $data['facility_id'],
                'start_date' => $start->toDateString(),
                'expires_at' => $expires->toDateString(),
                'total_sessions' => $trainerPackage->total_sessions,
                'used_sessions' => 0,
                'status' => 'pending',
            ]);

            Transaction::create([
                'user_id' => $member->id,
                'customer_type' => 'member',
                'transactionable_type' => MemberTrainerPackage::class,
                'transactionable_id' => $memberPackage->id,
                'customer_name' => $member->name,
                'total_amount' => $trainerPackage->price,
                'payment_method' => 'bank_transfer',
                'payment_status' => 'unpaid',
            ]);
        });

        return redirect()->route('member.payments')
            ->with('success', 'Paket trainer berhasil dipesan. Silakan lanjutkan proses pembayaran melalui admin.');
    }
}
