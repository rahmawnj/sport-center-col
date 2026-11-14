<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $adminRole = Role::where('name', 'Superadmin')->firstOrFail();

        $admins = User::query()
            ->where('role_id', $adminRole->id)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toISOString(),
            ]);

        return Inertia::render('admins/Index', [
            'admins' => $admins,
            'filters' => ['search' => $search],
            'stats' => ['total' => User::where('role_id', $adminRole->id)->count()],
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $admin = new User();
        $admin->name = $data['name'];
        $admin->email = $data['email'];
        $admin->password = $data['password'];
        $admin->role_id = Role::where('name', 'Superadmin')->firstOrFail()->id;
        $admin->email_verified_at = now();
        $admin->save();

        return back()->with('success', 'Admin berhasil ditambahkan.');
    }

    public function update(Request $request, User $admin): RedirectResponse
    {
        $adminRole = Role::where('name', 'Superadmin')->firstOrFail();
        abort_unless($admin->role_id === $adminRole->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $admin->name = $data['name'];
        $admin->email = $data['email'];
        if (! empty($data['password'])) {
            $admin->password = $data['password'];
        }
        $admin->save();

        return back()->with('success', 'Data admin berhasil diperbarui.');
    }

    public function destroy(Request $request, User $admin): RedirectResponse
    {
        $adminRole = Role::where('name', 'Superadmin')->firstOrFail();
        abort_unless($admin->role_id === $adminRole->id, 404);

        if ($admin->id === $request->user()->id) {
            return back()->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        if (User::where('role_id', $adminRole->id)->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu akun admin.');
        }

        $admin->delete();

        return back()->with('success', 'Admin berhasil dihapus.');
    }
}
