<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class MembershipRegistrationController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function index(Request $request): Response
    {
        $this->authorizeAdmin();
        $search = trim((string) $request->input('search', ''));

        $members = User::query()
            ->with('role:id,name')
            ->whereHas('role', fn ($query) => $query->where('name', 'Member'))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'created_at' => $member->created_at?->format('Y-m-d'),
            ]);

        return Inertia::render('memberships/Index', [
            'members' => $members,
            'filters' => ['search' => $search],
            'stats' => [
                'total' => User::whereHas('role', fn ($query) => $query->where('name', 'Member'))->count(),
                'new_this_month' => User::whereHas('role', fn ($query) => $query->where('name', 'Member'))->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $memberRole = Role::query()->where('name', 'Member')->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role_id' => $memberRole->id,
        ]);

        return back()->with('success', 'Member berhasil didaftarkan.');
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $this->authorizeAdmin();
        $memberRole = Role::query()->where('name', 'Member')->firstOrFail();
        abort_unless($member->role_id === $memberRole->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $member->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $member->name = $data['name'];
        $member->email = $data['email'];
        $member->phone = $data['phone'] ?? null;
        if (!empty($data['password'])) {
            $member->password = $data['password'];
        }
        $member->save();

        return back()->with('success', 'Data member berhasil diperbarui.');
    }

    public function destroy(User $member): RedirectResponse
    {
        $this->authorizeAdmin();
        $memberRole = Role::query()->where('name', 'Member')->firstOrFail();
        abort_unless($member->role_id === $memberRole->id, 404);
        abort_if($member->id === auth()->id(), 422, 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');

        $member->delete();
        return back()->with('success', 'Member berhasil dinonaktifkan.');
    }
}
