<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\MembershipPackage;
use App\Models\Trainer;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicHomeController extends Controller
{
    public function index(): Response
    {
        $facilities = Facility::query()
            ->where('status', 'active')
            ->where('is_bookable_online', true)
            ->withCount(['courts' => fn ($query) => $query->where('status', 'available')])
            ->with(['packages' => fn ($query) => $query
                ->with(['pricingRules' => fn ($rules) => $rules->orderBy('price')])
                ->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn (Facility $facility) => [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
                'available_courts' => $facility->courts_count,
                'packages' => $facility->packages->map(fn ($package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'type' => $package->type,
                    'duration_minutes' => $package->duration_minutes,
                    'duration_days' => $package->duration_days,
                    'pricing_rules' => $package->pricingRules->map(fn ($rule) => [
                        'price' => (float) $rule->price,
                        'day_type' => $rule->day_type,
                        'start_time' => $rule->start_time ? substr((string) $rule->start_time, 0, 5) : null,
                        'end_time' => $rule->end_time ? substr((string) $rule->end_time, 0, 5) : null,
                    ])->values(),
                ])->values(),
            ])->values();

        $trainers = Trainer::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(4)
            ->get(['id', 'name', 'specialty']);

        $membershipPackages = MembershipPackage::query()
            ->whereNull('deleted_at')
            ->orderBy('price')
            ->limit(3)
            ->get(['id', 'name', 'price', 'duration_days', 'description']);

        return Inertia::render('Welcome', [
            'facilities' => $facilities,
            'trainers' => $trainers,
            'membershipPackages' => $membershipPackages,
            'stats' => [
                'facilities' => $facilities->count(),
                'courts' => DB::table('courts')
                    ->where('status', 'available')
                    ->whereIn('facility_id', $facilities->pluck('id'))
                    ->count(),
                'trainers' => Trainer::query()->whereNull('deleted_at')->count(),
                'membershipPackages' => MembershipPackage::query()->whereNull('deleted_at')->count(),
            ],
        ]);
    }
}
