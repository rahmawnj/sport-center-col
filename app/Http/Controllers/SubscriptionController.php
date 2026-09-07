<?php

namespace App\Http\Controllers;

use App\Models\MembershipPackage;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function index(Request $request): Response
    {
        $this->authorizeAdmin();

        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');

        $subscriptions = UserSubscription::query()
            ->with(['user:id,name,email,phone,role_id', 'membershipPackage:id,name,price,duration_days'])
            ->whereHas('user.role', fn ($query) => $query->where('name', 'Member'))
            ->when($search !== '', fn ($query) => $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (UserSubscription $subscription) => [
                'id' => $subscription->id,
                'user' => $subscription->user ? [
                    'id' => $subscription->user->id,
                    'name' => $subscription->user->name,
                    'email' => $subscription->user->email,
                ] : null,
                'package' => $subscription->membershipPackage ? [
                    'id' => $subscription->membershipPackage->id,
                    'name' => $subscription->membershipPackage->name,
                    'price' => $subscription->membershipPackage->price,
                    'duration_days' => $subscription->membershipPackage->duration_days,
                ] : null,
                'start_date' => $subscription->start_date?->format('Y-m-d'),
                'end_date' => $subscription->end_date?->format('Y-m-d'),
                'status' => $subscription->status,
            ]);

        return Inertia::render('subscriptions/Index', [
            'members' => User::query()
                ->whereHas('role', fn ($query) => $query->where('name', 'Member'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'packages' => MembershipPackage::query()
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'duration_days']),
            'subscriptions' => $subscriptions,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'total' => UserSubscription::whereHas('user.role', fn ($query) => $query->where('name', 'Member'))->count(),
                'active' => UserSubscription::whereHas('user.role', fn ($query) => $query->where('name', 'Member'))->where('status', 'active')->count(),
                'expired' => UserSubscription::whereHas('user.role', fn ($query) => $query->where('name', 'Member'))->where('status', 'expired')->count(),
                'cancelled' => UserSubscription::whereHas('user.role', fn ($query) => $query->where('name', 'Member'))->where('status', 'cancelled')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'membership_package_id' => ['required', 'integer', 'exists:membership_packages,id'],
            'status' => ['required', 'in:active,expired,cancelled'],
        ]);

        $memberRole = Role::query()->where('name', 'Member')->firstOrFail();
        abort_unless(User::whereKey($data['user_id'])->where('role_id', $memberRole->id)->exists(), 422);

        $package = MembershipPackage::query()->findOrFail($data['membership_package_id']);
        $startDate = now()->startOfDay();
        $endDate = $startDate->copy()->addDays((int) $package->duration_days);

        UserSubscription::create([
            'user_id' => $data['user_id'],
            'membership_package_id' => $data['membership_package_id'],
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => $data['status'],
        ]);

        return back()->with('success', 'Subscription berhasil dibuat.');
    }

    public function update(Request $request, UserSubscription $subscription): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'membership_package_id' => ['required', 'integer', 'exists:membership_packages,id'],
            'status' => ['required', 'in:active,expired,cancelled'],
        ]);

        $package = MembershipPackage::query()->findOrFail($data['membership_package_id']);
        $startDate = now()->startOfDay();
        $endDate = $startDate->copy()->addDays((int) $package->duration_days);

        $subscription->update([
            'membership_package_id' => $data['membership_package_id'],
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => $data['status'],
        ]);

        return back()->with('success', 'Subscription berhasil diperbarui.');
    }

    public function destroy(UserSubscription $subscription): RedirectResponse
    {
        $this->authorizeAdmin();
        $subscription->delete();

        return back()->with('success', 'Subscription berhasil dihapus.');
    }
}
