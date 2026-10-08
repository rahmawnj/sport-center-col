<?php

namespace App\Http\Controllers;

use App\Models\UserMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');

        $memberships = UserMembership::query()
            ->with(['user:id,name', 'package:id,name,duration_days'])
            ->when($search !== '', fn ($query) => $query->whereHas(
                'user',
                fn ($user) => $user->where('name', 'like', "%{$search}%")
            ))
            ->when($status === 'active', fn ($query) => $query
                ->where('status', '!=', 'cancelled')
                ->whereDate('end_date', '>=', today()))
            ->when($status === 'expired', fn ($query) => $query
                ->where('status', '!=', 'cancelled')
                ->whereDate('end_date', '<', today()))
            ->when($status === 'cancelled', fn ($query) => $query->where('status', 'cancelled'))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (UserMembership $membership) => $this->toRow($membership));

        return Inertia::render('memberships/Index', [
            'memberships' => $memberships,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'active' => UserMembership::where('status', '!=', 'cancelled')
                    ->whereDate('end_date', '>=', today())->count(),
                'expiring' => UserMembership::where('status', '!=', 'cancelled')
                    ->whereDate('end_date', '>=', today())
                    ->whereDate('end_date', '<=', today()->addDays(7))->count(),
                'expired' => UserMembership::where('status', '!=', 'cancelled')
                    ->whereDate('end_date', '<', today())->count(),
            ],
        ]);
    }

    public function show(UserMembership $membership): Response
    {
        $membership->load(['user.memberProfile', 'package.pricingRules']);

        $cancelled = $membership->status === 'cancelled';
        $expired = ! $cancelled && $membership->end_date->lt(Carbon::today());

        return Inertia::render('memberships/Show', [
            'membership' => [
                'id' => $membership->id,
                'status' => $cancelled ? 'cancelled' : ($expired ? 'expired' : 'active'),
                'start_date' => $membership->start_date?->toDateString(),
                'end_date' => $membership->end_date?->toDateString(),
                'days_left' => (int) Carbon::today()->diffInDays($membership->end_date, false),
                'member' => [
                    'name' => $membership->user?->name,
                    'email' => $membership->user?->email,
                    'member_code' => $membership->user?->memberProfile?->member_code,
                    'qr_code' => $membership->user?->memberProfile?->qr_code,
                ],
                'package' => [
                    'name' => $membership->package?->name,
                    'type' => $membership->package?->type,
                    'duration_days' => $membership->package?->duration_days,
                    'price' => (float) ($membership->package?->pricingRules->min('price') ?? 0),
                ],
            ],
        ]);
    }

    public function update(Request $request, UserMembership $membership): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $status = $membership->status === 'cancelled'
            ? 'cancelled'
            : (Carbon::parse($data['end_date'])->lt(Carbon::today()) ? 'expired' : 'active');

        $membership->update([
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $status,
        ]);

        return back()->with('success', 'Tanggal membership diperbarui.');
    }

    public function renew(Request $request, UserMembership $membership): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
        ]);

        $membership->load('package');

        $duration = max(1, (int) ($membership->package->duration_days ?? 30));
        $base = $membership->end_date && $membership->end_date->gt(Carbon::today())
            ? $membership->end_date->copy()
            : Carbon::today();
        $newEnd = $base->copy()->addDays($duration);
        $price = (float) ($membership->package->pricingRules()->min('price') ?? 0);

        DB::transaction(function () use ($membership, $data, $newEnd, $price) {
            $membership->update([
                'end_date' => $newEnd->toDateString(),
                'status' => 'active',
            ]);

            $membership->transactions()->create([
                'user_id' => $membership->user_id,
                'amount' => $price,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'paid',
            ]);
        });

        return back()->with('success', 'Membership diperpanjang sampai '.$newEnd->translatedFormat('d M Y').'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(UserMembership $membership): array
    {
        $cancelled = $membership->status === 'cancelled';
        $expired = ! $cancelled && $membership->end_date->lt(Carbon::today());

        return [
            'id' => $membership->id,
            'member_name' => $membership->user?->name,
            'package_name' => $membership->package?->name,
            'start_date' => $membership->start_date?->toDateString(),
            'end_date' => $membership->end_date?->toDateString(),
            'days_left' => (int) Carbon::today()->diffInDays($membership->end_date, false),
            'status' => $cancelled ? 'cancelled' : ($expired ? 'expired' : 'active'),
            'duration_days' => $membership->package?->duration_days,
        ];
    }
}
