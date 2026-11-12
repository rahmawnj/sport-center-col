<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Package;
use App\Models\SportClass;
use App\Models\Trainer;
use App\Models\TrainerPackage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicHomeController extends Controller
{
    public function showTrainer(Trainer $trainer): Response
    {
        $trainer->load(['facilities' => fn ($query) => $query
            ->where('facilities.status', 'active')
            ->where('facilities.is_bookable_online', true)
            ->select('facilities.id', 'facilities.name')]);

        $facilityIds = $trainer->facilities->pluck('id');

        $packages = TrainerPackage::query()
            ->where('is_active', true)
            ->whereIn('facility_id', $facilityIds)
            ->whereHas('facility', fn ($query) => $query
                ->where('status', 'active')
                ->where('is_bookable_online', true)
                ->where('has_membership', true))
            ->with('facility:id,name')
            ->orderBy('price')
            ->get(['id', 'facility_id', 'name', 'total_sessions', 'duration_days', 'price', 'description'])
            ->map(fn ($package) => [
                'id' => $package->id,
                'facility_id' => $package->facility_id,
                'name' => $package->name,
                'total_sessions' => $package->total_sessions,
                'duration_days' => $package->duration_days,
                'price' => (float) $package->price,
                'description' => $package->description,
                'sports' => ($package->facility ? collect([$package->facility->name]) : collect()),
            ])->values();

        return Inertia::render('PublicTrainerShow', [
            'trainer' => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'specialty' => $trainer->specialty,
                'sports' => $trainer->facilities->pluck('name')->values(),
            ],
            'packages' => $packages,
        ]);
    }

    public function index(): Response
    {
        $facilities = Facility::query()
            ->where('status', 'active')
            ->where('has_booking', true)
            ->where('is_bookable_online', true)
            ->withCount(['courts' => fn ($query) => $query->where('status', 'available')])
            ->with(['packages' => fn ($query) => $query
                ->where('type', '!=', 'membership')
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

        $classes = SportClass::query()
            ->where('is_active', true)
            ->whereHas('facility', fn ($query) => $query
                ->where('status', 'active')
                ->where('has_session', true)
                ->where('is_bookable_online', true))
            ->with([
                'facility:id,name',
                'schedules' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with('trainer:id,name')
                    ->orderByRaw("FIELD(day, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
                    ->orderBy('start_time'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (SportClass $class) => [
                'id' => $class->id,
                'name' => $class->name,
                'description' => $class->description,
                'facility' => $class->facility?->name,
                'duration_minutes' => (int) $class->duration_minutes,
                'schedules' => $class->schedules->map(fn ($schedule) => [
                    'id' => $schedule->id,
                    'day' => $schedule->day,
                    'start_time' => substr((string) $schedule->start_time, 0, 5),
                    'end_time' => substr((string) $schedule->end_time, 0, 5),
                    'trainer' => $schedule->trainer?->name,
                    'capacity' => (int) ($schedule->capacity ?? $class->default_capacity),
                    'price' => (float) $schedule->price,
                ])->values(),
            ])
            ->filter(fn ($class) => $class['schedules']->isNotEmpty())
            ->values();

        $trainers = Trainer::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(4)
            ->get(['id', 'name', 'specialty']);

        $trainerPackages = TrainerPackage::query()
            ->where('is_active', true)
            ->whereHas('facility', fn ($query) => $query
                ->where('status', 'active')
                ->where('is_bookable_online', true)
                ->where('has_membership', true))
            ->with(['facility:id,name', 'facility.trainers:id,name,specialty'])
            ->orderBy('price')
            ->get(['id', 'facility_id', 'name', 'total_sessions', 'duration_days', 'price', 'description'])
            ->map(fn (TrainerPackage $package) => [
                'id' => $package->id,
                'facility_id' => $package->facility_id,
                'name' => $package->name,
                'total_sessions' => $package->total_sessions,
                'duration_days' => $package->duration_days,
                'price' => (float) $package->price,
                'description' => $package->description,
                'sports' => ($package->facility ? collect([$package->facility->name]) : collect()),
                'trainers' => $package->facility?->trainers->map(fn ($trainer) => ['id' => $trainer->id, 'name' => $trainer->name, 'specialty' => $trainer->specialty])->values() ?? collect(),
            ])->values();

        $membershipPackages = Package::query()
            ->where('type', 'membership')
            ->whereNotNull('duration_days')
            ->whereHas('facility', fn ($query) => $query
                ->where('status', 'active')
                ->where('is_bookable_online', true)
                ->where('has_membership', true))
            ->with('facility:id,name,slug')
            ->orderBy('duration_days')
            ->get()
            ->map(fn (Package $package) => [
                'id' => $package->id,
                'facility_id' => $package->facility_id,
                'name' => $package->name,
                'facility' => $package->facility?->name,
                'duration_days' => $package->duration_days,
                'price' => (float) $package->price,
                'description' => 'Membership '.$package->facility?->name.' selama '.$package->duration_days.' hari.',
            ])->values();

        return Inertia::render('Welcome', [
            'facilities' => $facilities,
            'classes' => $classes,
            'trainers' => $trainers,
            'membershipPackages' => $membershipPackages,
            'trainerPackages' => $trainerPackages,
            'stats' => [
                'facilities' => $facilities->count(),
                'courts' => DB::table('courts')
                    ->where('status', 'available')
                    ->whereIn('facility_id', $facilities->pluck('id'))
                    ->count(),
                'trainers' => Trainer::query()->whereNull('deleted_at')->count(),
                'membershipPackages' => Package::query()
                    ->where('type', 'membership')
                    ->whereNotNull('duration_days')
                    ->whereHas('facility', fn ($query) => $query->where('status', 'active')->where('is_bookable_online', true)->where('has_membership', true))
                    ->count(),
            ],
        ]);
    }
}
