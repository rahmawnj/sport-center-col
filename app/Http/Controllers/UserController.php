<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function index(Request $request): Response
    {
        $this->authorizeAdmin();

        $search = trim((string) $request->input('search', ''));
        $roleId = $request->input('role_id');
        $status = $request->input('status', 'all');

        $users = User::query()
            ->with('role:id,name')
            ->withCount(['transactions', 'userSubscriptions'])
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($roleId, fn ($query) => $query->where('role_id', $roleId))
            ->when($status === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($status === 'deleted', fn ($query) => $query->onlyTrashed())
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'created_at' => $user->created_at?->toISOString(),
                'deleted_at' => $user->deleted_at?->toISOString(),
                'role' => $user->role ? ['id' => $user->role->id, 'name' => $user->role->name] : null,
                'transactions_count' => $user->transactions_count,
                'subscriptions_count' => $user->user_subscriptions_count,
            ]);

        return Inertia::render('users/Index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'role_id' => $roleId,
                'status' => $status,
            ],
            'stats' => [
                'total' => User::count(),
                'admins' => User::whereHas('role', fn ($q) => $q->whereIn('name', ['Superadmin', 'Admin']))->count(),
                'members' => User::whereHas('role', fn ($q) => $q->where('name', 'Member'))->count(),
                'verified' => User::whereNotNull('email_verified_at')->count(),
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
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create($data);

        return back()->with('success', 'User berhasil dibuat.');
    }

    public function show(User $user): Response
    {
        $this->authorizeAdmin();

        $user->load([
            'role',
            'userSubscriptions.membershipPackage',
            'transactions' => fn ($query) => $query->latest()->limit(8),
        ]);

        return Inertia::render('users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'created_at' => $user->created_at?->toISOString(),
                'role' => $user->role ? ['id' => $user->role->id, 'name' => $user->role->name] : null,
                'subscriptions' => $user->userSubscriptions->map(fn ($subscription) => [
                    'id' => $subscription->id,
                    'package' => $subscription->membershipPackage?->name,
                    'start_date' => $subscription->start_date?->format('Y-m-d'),
                    'end_date' => $subscription->end_date?->format('Y-m-d'),
                    'status' => $subscription->status,
                    'used_sessions' => $subscription->used_sessions,
                    'session_quota' => $subscription->membershipPackage?->session_quota,
                ])->values(),
                'transactions' => $user->transactions->map(fn ($transaction) => [
                    'id' => $transaction->id,
                    'booking_code' => $transaction->booking_code,
                    'payment_method' => $transaction->payment_method,
                    'payment_status' => $transaction->payment_status,
                    'total_amount' => $transaction->total_amount,
                    'created_at' => $transaction->created_at?->toISOString(),
                ])->values(),
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        abort_if($user->is(auth()->user()), 422, 'Kamu tidak dapat menghapus akun sendiri.');

        $user->delete();

        return back()->with('success', 'User berhasil dinonaktifkan.');
    }
}
