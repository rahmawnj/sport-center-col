<?php

namespace App\\Http\\Controllers;

use App\\Models\\Facility;
use App\\Models\\Package;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;
use Inertia\\Inertia;
use Inertia\\Response;

class MembershipPackageController extends Controller
{
    public function index(): Response
    {
        $packages = Package::query()
            ->where('type', 'membership')
            ->whereHas('facility', fn ($query) => $query->where('has_membership', true))
            ->with(['facility:id,name', 'pricingRules'])
            ->latest()
            ->paginate(10)
            ->through(fn (Package $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'facility_id' => $package->facility_id,
                'facility_name' => $package->facility?->name,
                'duration_days' => $package->duration_days,
                'pricing_rules' => $package->pricingRules->map(fn ($rule) => [
                    'id' => $rule->id,
                    'day_type' => $rule->day_type,
                    'date_start' => $rule->date_start?->toDateString(),
                    'date_end' => $rule->date_end?->toDateString(),
                    'start_time' => $rule->start_time ? substr((string) $rule->start_time, 0, 5) : null,
                    'end_time' => $rule->end_time ? substr((string) $rule->end_time, 0, 5) : null,
                    'price' => (float) $rule->price,
                    'priority' => $rule->priority,
                ])->values(),
                'member_count' => $package->userMemberships()->count(),
            ])
            ->withQueryString();

        return Inertia::render('membership-packages/Index', [
            'packages' => $packages,
            'facilities' => Facility::query()
                ->where('status', 'active')
                ->where('has_membership', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'stats' => [
                'packages' => Package::where('type', 'membership')->whereHas('facility', fn ($query) => $query->where('has_membership', true))->count(),
                'members' => DB::table('user_memberships')
                    ->join('packages', 'packages.id', '=', 'user_memberships.package_id')
                    ->where('packages.type', 'membership')
                    ->distinct('user_memberships.user_id')
                    ->count('user_memberships.user_id'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->membershipRules());
        $pricingRules = $data['pricing_rules'];
        unset($data['pricing_rules']);
        $data['type'] = 'membership';
        $data['duration_minutes'] = 0;

        DB::transaction(function () use ($data, $pricingRules) {
            $package = Package::create($data);
            $package->pricingRules()->createMany($pricingRules);
        });

        return back()->with('success', 'Paket membership berhasil ditambahkan.');
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        abort_unless($package->type === 'membership', 404);

        $data = $request->validate($this->membershipRules());
        $pricingRules = $data['pricing_rules'];
        unset($data['pricing_rules']);
        $data['type'] = 'membership';
        $data['duration_minutes'] = 0;

        DB::transaction(function () use ($package, $data, $pricingRules) {
            $package->update($data);
            $package->pricingRules()->delete();
            $package->pricingRules()->createMany($pricingRules);
        });

        return back()->with('success', 'Paket membership berhasil diperbarui.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        abort_unless($package->type === 'membership', 404);

        if ($package->userMemberships()->exists()) {
            return back()->with('error', 'Paket sudah dimiliki member dan tidak dapat dihapus. Edit paketnya atau pertahankan untuk riwayat member.');
        }

        $package->pricingRules()->delete();
        $package->delete();

        return back()->with('success', 'Paket membership berhasil dihapus.');
    }

    private function membershipRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'facility_id' => ['required', 'integer', Rule::exists('facilities', 'id')->where(fn ($query) => $query->where('has_membership', true)->where('status', 'active'))],
            'duration_days' => ['required', 'integer', 'min:1'],
            'pricing_rules' => ['required', 'array', 'min:1'],
            'pricing_rules.*.day_type' => ['required', Rule::in(['weekday', 'weekend', 'all'])],
            'pricing_rules.*.date_start' => ['nullable', 'date'],
            'pricing_rules.*.date_end' => ['nullable', 'date'],
            'pricing_rules.*.start_time' => ['nullable', 'date_format:H:i', 'required_with:pricing_rules.*.end_time'],
            'pricing_rules.*.end_time' => ['nullable', 'date_format:H:i', 'after:pricing_rules.*.start_time'],
            'pricing_rules.*.price' => ['required', 'numeric', 'min:0'],
            'pricing_rules.*.priority' => ['nullable', 'integer'],
        ];
    }
}
