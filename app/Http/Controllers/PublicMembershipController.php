<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicMembershipController extends Controller
{
    public function index(Request $request): Response
    {
        $packages = $this->availablePackages()
            ->get()
            ->map(fn (Package $package) => $this->packageData($package));

        return Inertia::render('membership/Index', [
            'packages' => $packages,
            'currentUser' => $request->user()
                ? [
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ]
                : null,
        ]);
    }

    public function show(int $package): Response
    {
        $item = $this->availablePackages()
            ->whereKey($package)
            ->firstOrFail();

        return Inertia::render('membership/Show', [
            'package' => $this->packageData($item),
        ]);
    }

    private function availablePackages()
    {
        return Package::query()
            ->with([
                'facility:id,name,slug,status,is_bookable_online,has_membership',
                'membershipPricingRules' => fn ($query) => $query
                    ->where(fn ($rules) => $rules->whereNull('date_start')->orWhereDate('date_start', '<=', today()))
                    ->where(fn ($rules) => $rules->whereNull('date_end')->orWhereDate('date_end', '>=', today()))
                    ->orderBy('price'),
            ])
            ->where('type', 'membership')
            ->whereNotNull('duration_days')
            ->whereHas('facility', fn ($query) => $query
                ->where('status', 'active')
                ->where('is_bookable_online', true)
                ->where('has_membership', true));
    }

    private function packageData(Package $package): array
    {
        $prices = $package->membershipPricingRules->pluck('price')->map(fn ($price) => (float) $price)->values();

        return [
            'id' => $package->id,
            'name' => $package->name,
            'duration_days' => $package->duration_days,
            'facility' => $package->facility?->name,
            'facility_slug' => $package->facility?->slug,
            'prices' => $prices,
            'price' => $prices->isEmpty() ? null : $prices->min(),
            'description' => 'Membership '.$package->facility?->name.' selama '.$package->duration_days.' hari untuk mendukung rutinitas olahragamu.',
        ];
    }
}
