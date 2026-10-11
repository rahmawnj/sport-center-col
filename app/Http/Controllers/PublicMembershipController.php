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
        $packages = Package::query()
            ->with([
                'facility:id,name,slug,status,is_bookable_online',
                'pricingRules' => fn ($query) => $query
                    ->where(function ($rules) {
                        $rules->whereNull('date_start')->orWhereDate('date_start', '<=', today());
                    })
                    ->where(function ($rules) {
                        $rules->whereNull('date_end')->orWhereDate('date_end', '>=', today());
                    })
                    ->orderBy('price'),
            ])
            ->where('type', 'membership')
            ->whereNotNull('duration_days')
            ->whereHas('facility', fn ($query) => $query
                ->where('status', 'active')
                ->where('is_bookable_online', true))
            ->orderBy('duration_days')
            ->orderBy('name')
            ->get()
            ->map(fn (Package $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'duration_days' => $package->duration_days,
                'facility' => $package->facility?->name,
                'facility_slug' => $package->facility?->slug,
                'prices' => $package->pricingRules
                    ->pluck('price')
                    ->map(fn ($price) => (float) $price)
                    ->values(),
                'price' => $package->pricingRules->isEmpty()
                    ? null
                    : (float) $package->pricingRules->min('price'),
                'description' => $this->descriptionFor($package),
            ]);

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

    private function descriptionFor(Package $package): string
    {
        if (preg_match('/(\d+)\s*sesi/i', $package->name, $matches)) {
            return 'Paket ' . $matches[1] . ' sesi untuk menjaga rutinitas latihanmu tetap konsisten.';
        }

        return 'Akses membership selama ' . $package->duration_days . ' hari untuk rutinitas olahraga yang lebih teratur.';
    }
}
