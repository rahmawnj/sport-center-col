<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MemberProfile;
use App\Models\MemberTrainerPackage;
use App\Models\Package;
use App\Models\Role;
use App\Models\Trainer;
use App\Models\TrainerPackage;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PublicMembershipRegistrationController extends Controller
{
    public function create(Request $request): Response
    {
        $package = $this->availableMembershipPackages()
            ->whereKey($request->integer('package_id'))
            ->firstOrFail();

        $trainerPackage = null;
        $trainer = null;

        if ($request->filled('trainer_package_id')) {
            $trainerPackage = TrainerPackage::query()
                ->where('is_active', true)
                ->where('facility_id', $package->facility_id)
                ->findOrFail($request->integer('trainer_package_id'));
        }

        if ($request->filled('trainer_id')) {
            abort_unless($trainerPackage, 404);
            $trainer = Trainer::query()
                ->whereHas('facilities', fn ($query) => $query->where('facilities.id', $package->facility_id))
                ->findOrFail($request->integer('trainer_id'));
        }

        abort_if($trainerPackage && ! $trainer, 404, 'Pilih trainer untuk add-on yang dipilih.');

        return Inertia::render('membership/Register', [
            'selection' => [
                'package' => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'facility_id' => $package->facility_id,
                    'facility' => $package->facility?->name,
                    'duration_days' => (int) $package->duration_days,
                    'price' => (float) $package->price,
                ],
                'trainer_package' => $trainerPackage ? [
                    'id' => $trainerPackage->id,
                    'name' => $trainerPackage->name,
                    'total_sessions' => (int) $trainerPackage->total_sessions,
                    'duration_days' => (int) $trainerPackage->duration_days,
                    'price' => (float) $trainerPackage->price,
                ] : null,
                'trainer' => $trainer ? [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'specialty' => $trainer->specialty,
                ] : null,
                'total' => (float) $package->price + (float) ($trainerPackage?->price ?? 0),
                'payment_method' => $request->input('payment_method') === 'cash' ? 'cash' : 'bank_transfer',
            ],
            'currentUser' => $request->user()?->role?->name === 'Member' && $request->user()->memberProfile()->exists() ? [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isExistingMember = $request->user()?->role?->name === 'Member'
            && $request->user()->memberProfile()->exists();

        $rules = [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'trainer_package_id' => ['nullable', 'integer', 'exists:trainer_packages,id'],
            'trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
            'terms_accepted' => ['required', 'accepted'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash'])],
        ];

        if (! $isExistingMember) {
            $rules += [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'phone' => ['required', 'string', 'max:25'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ];
        }

        $data = $request->validate($rules);
        $package = $this->availableMembershipPackages()->findOrFail($data['package_id']);
        $trainerPackage = null;
        $trainer = null;

        if (! empty($data['trainer_package_id'])) {
            $trainerPackage = TrainerPackage::query()
                ->where('is_active', true)
                ->where('facility_id', $package->facility_id)
                ->findOrFail($data['trainer_package_id']);

            if (empty($data['trainer_id'])) {
                return back()->withErrors(['trainer_id' => 'Pilih trainer untuk paket add-on yang kamu pilih.'])->withInput();
            }

            $trainer = Trainer::query()
                ->whereHas('facilities', fn ($query) => $query->where('facilities.id', $package->facility_id))
                ->findOrFail($data['trainer_id']);
        } elseif (! empty($data['trainer_id'])) {
            return back()->withErrors(['trainer_id' => 'Trainer hanya dapat dipilih jika paket add-on dipilih.'])->withInput();
        }

        $user = DB::transaction(function () use ($request, $data, $package, $trainerPackage, $trainer, $isExistingMember) {
            $user = $isExistingMember ? $request->user() : null;

            if (! $user) {
                $memberRole = Role::firstOrCreate(['name' => 'Member']);
                $user = new User([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                ]);
                $user->role()->associate($memberRole);
                $user->save();

                MemberProfile::create([
                    'user_id' => $user->id,
                    'phone' => $data['phone'],
                ]);
            }

            $start = today();
            $membershipEnd = $start->copy()->addDays(max(1, (int) $package->duration_days) - 1);

            $membership = UserMembership::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'device_id' => Device::forFacility($package->facility_id)?->id,
                'start_date' => $start->toDateString(),
                'end_date' => $membershipEnd->toDateString(),
                'status' => 'active',
            ]);

            if ($trainerPackage && $trainer) {
                $trainerEnd = $start->copy()->addDays(max(1, (int) $trainerPackage->duration_days) - 1);

                MemberTrainerPackage::create([
                    'user_id' => $user->id,
                    'trainer_package_id' => $trainerPackage->id,
                    'trainer_id' => $trainer->id,
                    'facility_id' => $package->facility_id,
                    'start_date' => $start->toDateString(),
                    'expires_at' => $trainerEnd->toDateString(),
                    'total_sessions' => $trainerPackage->total_sessions,
                    'used_sessions' => 0,
                    'status' => 'pending',
                ]);
            }

            Transaction::create([
                'user_id' => $user->id,
                'transactionable_type' => 'membership',
                'guest_name' => $user->name,
                'total_amount' => (float) $package->price + (float) ($trainerPackage?->price ?? 0),
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
            ]);

            return $user;
        });

        if (! $isExistingMember) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()->route('member.payments')
            ->with('success', 'Checkout berhasil dibuat. Membership dan add-on trainer tergabung dalam satu transaksi.');
    }

    private function availableMembershipPackages()
    {
        return Package::query()
            ->with('facility:id,name,status,is_bookable_online,has_membership')
            ->where('type', 'membership')
            ->whereNotNull('duration_days')
            ->whereHas('facility', fn ($query) => $query
                ->where('status', 'active')
                ->where('is_bookable_online', true)
                ->where('has_membership', true));
    }
}
