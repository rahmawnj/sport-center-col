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
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PublicMembershipRegistrationController extends Controller
{
    public function create(Request $request): Response
    {
        $isTrainerOnly = $request->input('purchase_type') === 'trainer';
        $package = null;
        $trainerPackage = null;
        $trainer = null;

        if (! $isTrainerOnly) {
            $package = $this->availableMembershipPackages()
                ->whereKey($request->integer('package_id'))
                ->firstOrFail();
        }

        if ($isTrainerOnly || $request->filled('trainer_package_id')) {
            $trainerPackage = TrainerPackage::query()
                ->where('is_active', true)
                ->whereHas('facility', fn ($query) => $query
                    ->where('status', 'active')
                    ->where('is_bookable_online', true)
                    ->where('has_membership', true))
                ->findOrFail($request->integer('trainer_package_id'));
            if ($package) {
                abort_unless((int) $trainerPackage->facility_id === (int) $package->facility_id, 404);
            }
        }

        if ($request->filled('trainer_id')) {
            abort_unless($trainerPackage, 404);
            $facilityId = $package?->facility_id ?? $trainerPackage->facility_id;
            $trainer = Trainer::query()
                ->whereHas('facilities', fn ($query) => $query->where('facilities.id', $facilityId))
                ->findOrFail($request->integer('trainer_id'));
        }

        abort_if($trainerPackage && ! $trainer, 404, 'Pilih trainer untuk paket yang dipilih.');

        return Inertia::render('membership/Register', [
            'selection' => [
                'purchase_type' => $isTrainerOnly ? 'trainer' : 'membership',
                'package' => $package ? [
                    'id' => $package->id,
                    'name' => $package->name,
                    'facility_id' => $package->facility_id,
                    'facility' => $package->facility?->name,
                    'duration_days' => (int) $package->duration_days,
                    'price' => (float) $package->price,
                ] : null,
                'trainer_package' => $trainerPackage ? [
                    'id' => $trainerPackage->id,
                    'facility_id' => $trainerPackage->facility_id,
                    'facility' => $trainerPackage->facility?->name,
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
                'total' => (float) ($package?->price ?? 0) + (float) ($trainerPackage?->price ?? 0),
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
        try {
            return $this->processStore($request);
        } catch (Throwable $exception) {
            Log::error('Membership registration checkout gagal.', [
                'user_id' => $request->user()?->id,
                'purchase_type' => $request->input('purchase_type', 'membership'),
                'package_id' => $request->input('package_id'),
                'trainer_package_id' => $request->input('trainer_package_id'),
                'trainer_id' => $request->input('trainer_id'),
                'payment_method' => $request->input('payment_method'),
                'request_url' => $request->fullUrl(),
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    private function processStore(Request $request): RedirectResponse
    {
        $isExistingMember = $request->user()?->role?->name === 'Member'
            && $request->user()->memberProfile()->exists();
        $isTrainerOnly = $request->input('purchase_type') === 'trainer';

        $rules = [
            'package_id' => [$isTrainerOnly ? 'nullable' : 'required', 'integer', 'exists:packages,id'],
            'trainer_package_id' => [$isTrainerOnly ? 'required' : 'nullable', 'integer', 'exists:trainer_packages,id'],
            'trainer_id' => ['nullable', 'required_with:trainer_package_id', 'integer', 'exists:trainers,id'],
            'purchase_type' => ['nullable', Rule::in(['membership', 'trainer'])],
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
        $package = ! empty($data['package_id'])
            ? $this->availableMembershipPackages()->findOrFail($data['package_id'])
            : null;
        $trainerPackage = null;
        $trainer = null;
        $facilityId = $package?->facility_id;

        if (! empty($data['trainer_package_id'])) {
            $trainerPackage = TrainerPackage::query()
                ->where('is_active', true)
                ->whereHas('facility', fn ($query) => $query
                    ->where('status', 'active')
                    ->where('is_bookable_online', true)
                    ->where('has_membership', true))
                ->findOrFail($data['trainer_package_id']);
            $facilityId ??= $trainerPackage->facility_id;
            abort_unless((int) $trainerPackage->facility_id === (int) $facilityId, 422, 'Fasilitas paket trainer tidak sesuai.');
            $trainer = Trainer::query()
                ->whereHas('facilities', fn ($query) => $query->where('facilities.id', $facilityId))
                ->findOrFail($data['trainer_id']);
        }

        if (! $package && ! $trainerPackage) {
            abort(422, 'Pilih paket yang ingin dibeli.');
        }

        $result = DB::transaction(function () use ($request, $data, $package, $trainerPackage, $trainer, $facilityId, $isExistingMember) {
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
                MemberProfile::create(['user_id' => $user->id, 'phone' => $data['phone']]);
            }

            // Serialisasi pembelian member yang sama agar dua checkout bersamaan
            // tidak mendapatkan tanggal mulai yang bertabrakan.
            User::query()->lockForUpdate()->findOrFail($user->id);

            // Semua pembelian membership dan trainer memakai satu antrean
            // global per member, tanpa membedakan fasilitas atau jenis paket.
            $latestMembershipEnd = UserMembership::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->whereDate('end_date', '>=', today())
                ->lockForUpdate()
                ->max('end_date');

            $latestTrainerEnd = MemberTrainerPackage::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->whereDate('expires_at', '>=', today())
                ->lockForUpdate()
                ->max('expires_at');

            $latestEnd = collect([$latestMembershipEnd, $latestTrainerEnd])
                ->filter()
                ->map(fn ($date) => \\Illuminate\\Support\\Carbon::parse($date))
                ->sort()
                ->last();

            $start = $latestEnd ? $latestEnd->copy()->addDay() : today();

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'transactionable_type' => $package ? 'membership' : 'trainer_package',
                'guest_name' => $user->name,
                'total_amount' => (float) ($package?->price ?? 0) + (float) ($trainerPackage?->price ?? 0),
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
            ]);

            if ($package) {
                $membershipEnd = $start->copy()->addDays(max(1, (int) $package->duration_days) - 1);
                UserMembership::create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'device_id' => Device::forFacility($package->facility_id)?->id,
                    'start_date' => $start->toDateString(),
                    'end_date' => $membershipEnd->toDateString(),
                    // Checkout is not activation. The membership becomes
                    // active only after its linked transaction is approved as paid.
                    'status' => 'pending',
                    'transaction_id' => $transaction->id,
                ]);
            }

            if ($trainerPackage && $trainer) {
                $trainerEnd = $start->copy()->addDays(max(1, (int) $trainerPackage->duration_days) - 1);
                MemberTrainerPackage::create([
                    'user_id' => $user->id,
                    'trainer_package_id' => $trainerPackage->id,
                    'trainer_id' => $trainer->id,
                    'facility_id' => $facilityId,
                    'start_date' => $start->toDateString(),
                    'expires_at' => $trainerEnd->toDateString(),
                    'total_sessions' => $trainerPackage->total_sessions,
                    'used_sessions' => 0,
                    'status' => 'pending',
                    'transaction_id' => $transaction->id,
                ]);
            }

            return ['user' => $user, 'transaction' => $transaction];
        });

        $user = $result['user'];
        $transaction = $result['transaction'];

        if (! $isExistingMember) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()->route('member.payments.show', $transaction->id)
            ->with('success', $package
                ? 'Checkout berhasil dibuat. Membership dan add-on trainer tergabung dalam satu transaksi.'
                : 'Checkout paket personal trainer berhasil dibuat.');
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
