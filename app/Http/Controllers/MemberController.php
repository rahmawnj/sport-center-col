<?php

namespace App\Http\Controllers;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $members = User::query()
            ->whereHas('memberProfile')
            ->with('memberProfile')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('memberProfile', fn ($profile) => $profile->where('member_code', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->memberProfile->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'member_code' => $user->memberProfile->member_code,
                'qr_code' => $user->memberProfile->qr_code,
                'phone' => $user->memberProfile->phone,
                'date_of_birth' => $user->memberProfile->date_of_birth?->toDateString(),
                'gender' => $user->memberProfile->gender,
                'address' => $user->memberProfile->address,
                'emergency_contact_name' => $user->memberProfile->emergency_contact_name,
                'emergency_contact_phone' => $user->memberProfile->emergency_contact_phone,
                'memberships_count' => $user->userMemberships()->count(),
                'created_at' => $user->created_at?->toISOString(),
            ]);

        return Inertia::render('members/Index', [
            'members' => $members,
            'filters' => ['search' => $search],
            'stats' => [
                'total' => MemberProfile::count(),
                'male' => MemberProfile::where('gender', 'male')->count(),
                'female' => MemberProfile::where('gender', 'female')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'member_code' => ['nullable', 'string', 'max:100', Rule::unique('member_profiles', 'member_code')],
            'phone' => ['required', 'string', 'max:25'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->memberProfile()->create($this->profileAttributes($data));

        return back()->with('success', 'Member berhasil ditambahkan.');
    }

    public function update(Request $request, MemberProfile $member): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member->user_id)],
            'member_code' => ['nullable', 'string', 'max:100', Rule::unique('member_profiles', 'member_code')->ignore($member->id)],
            'phone' => ['required', 'string', 'max:25'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $member->user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $member->update($this->profileAttributes($data));

        return back()->with('success', 'Data member berhasil diperbarui.');
    }

    public function destroy(MemberProfile $member): RedirectResponse
    {
        $user = $member->user;

        $member->delete();
        $user?->delete();

        return back()->with('success', 'Member berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function profileAttributes(array $data): array
    {
        return [
            'member_code' => $data['member_code'] ?? null,
            'phone' => $data['phone'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
        ];
    }
}
