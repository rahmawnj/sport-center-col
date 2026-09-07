<?php

namespace App\Http\Controllers;

use App\Models\MembershipPackage;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController extends Controller
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

        return Inertia::render('memberships/Index', [
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'membership_package_id' => ['required', 'integer', 'exists:membership_packages,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:active,expired,cancelled'],
        ]);

        $memberRole = Role::query()->where('name', 'Member')->firstOrFail();

        DB::transaction(function () use ($data, $memberRole): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role_id' => $memberRole->id,
            ]);

            UserSubscription::create([
                'user_id' => $user->id,
                'membership_package_id' => $data['membership_package_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'],
            ]);
        });

        return back()->with('success', 'Member dan membership berhasil didaftarkan.');
    }

    public function update(Request $request, UserSubscription $membership): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'membership_package_id' => ['required', 'integer', 'exists:membership_packages,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:active,expired,cancelled'],
        ]);

        $membership->update($data);

        return back()->with('success', 'Membership berhasil diperbarui.');
    }

    public function destroy(UserSubscription $membership): RedirectResponse
    {
        $this->authorizeAdmin();
        $membership->delete();
        return back()->with('success', 'Data membership berhasil dihapus.');
    }
}
