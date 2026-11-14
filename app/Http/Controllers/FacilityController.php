<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\BookingTimeSlot;
use App\Models\ClassSchedule;
use App\Models\SessionParticipant;
use App\Models\TrainerPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FacilityController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');

        $facilities = Facility::query()
            ->withCount(['courts', 'packages'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Facility $facility) => [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
                'is_bookable_online' => (bool) $facility->is_bookable_online,
                'has_membership' => (bool) $facility->has_membership,
                'has_membership' => (bool) $facility->has_membership,
                'has_booking' => (bool) $facility->has_booking,
                'has_session' => (bool) $facility->has_session,
                'turnaround_minutes' => $facility->turnaround_minutes,
                'status' => $facility->status,
                'courts_count' => $facility->courts_count,
                'packages_count' => $facility->packages_count,
                'created_at' => $facility->created_at?->toISOString(),
            ]);

        return Inertia::render('facilities/Index', [
            'facilities' => $facilities,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'total' => Facility::count(),
                'active' => Facility::where('status', 'active')->count(),
                'maintenance' => Facility::where('status', 'maintenance')->count(),
            ],
        ]);
    }

    public function show(Facility $facility): Response
    {
        $facility->load([
            'courts' => fn ($query) => $query->orderBy('name'),
            'packages' => fn ($query) => $query->orderBy('name'),
            'packages.pricingRules',
            'packages.trainerAddons.facilities',
            'trainers' => fn ($query) => $query->orderBy('name'),
            'devices' => fn ($query) => $query->with('court:id,name')->orderBy('name'),
            'classes' => fn ($query) => $query->with(['schedules' => fn ($schedules) => $schedules
                ->with(['trainer:id,name', 'participants.user:id,name'])
                ->withCount(['participants as registered_count' => fn ($participants) => $participants->whereIn('status', ['pending', 'confirmed'])])
                ->orderBy('day')->orderBy('start_time')]),
        ]);

        return Inertia::render('facilities/Show', [
            'facility' => [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
                'is_bookable_online' => (bool) $facility->is_bookable_online,
                'has_booking' => (bool) $facility->has_booking,
                'has_session' => (bool) $facility->has_session,
                'has_membership' => (bool) $facility->has_membership,
                'turnaround_minutes' => $facility->turnaround_minutes,
                'status' => $facility->status,
                'created_at' => $facility->created_at?->toISOString(),
                'courts' => $facility->courts->map(fn ($court) => [
                    'id' => $court->id,
                    'name' => $court->name,
                    'amenities' => $court->amenities ?? [],
                    'price_adjustment' => (float) $court->price_adjustment,
                    'status' => $court->status,
                ])->values(),
                'packages' => $facility->packages->map(fn ($package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'type' => $package->type,
                    'duration_minutes' => $package->duration_minutes,
                    'duration_days' => $package->duration_days,
                    'trainer_addons' => $package->trainerAddons
                        ->filter(fn ($trainer) => $facility->trainers->contains('id', $trainer->id))
                        ->map(fn ($trainer) => [
                        'trainer_id' => $trainer->id,
                        'trainer_name' => $trainer->name,
                        'price' => (float) $trainer->pivot->price,
                    ])->values(),
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
                ])->values(),
                'trainer_packages' => TrainerPackage::query()->where('facility_id', $facility->id)->orderBy('name')->get()->map(fn ($package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'total_sessions' => $package->total_sessions,
                    'duration_days' => $package->duration_days,
                    'price' => (float) $package->price,
                    'trainer_share_percentage' => (float) $package->trainer_share_percentage,
                    'description' => $package->description,
                    'is_active' => (bool) $package->is_active,
                ])->values(),
                'trainers' => $facility->trainers->map(fn ($trainer) => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'specialty' => $trainer->specialty,
                ])->values(),
                'classes' => $facility->classes->map(fn ($class) => [
                    'id' => $class->id,
                    'facility_id' => $class->facility_id,
                    'name' => $class->name,
                    'description' => $class->description,
                    'duration_minutes' => $class->duration_minutes,
                    'default_capacity' => $class->default_capacity,
                    'is_active' => (bool) $class->is_active,
                    'schedules' => $class->schedules->map(fn (ClassSchedule $schedule) => [
                        'id' => $schedule->id,
                        'trainer_id' => $schedule->trainer_id,
                        'trainer' => $schedule->trainer?->name,
                        'day' => $schedule->day,
                        'start_time' => substr((string) $schedule->start_time, 0, 5),
                        'end_time' => substr((string) $schedule->end_time, 0, 5),
                        'capacity' => $schedule->capacity,
                        'effective_capacity' => (int) ($schedule->capacity ?? $class->default_capacity),
                        'registered_count' => (int) $schedule->registered_count,
                        'price' => (float) $schedule->price,
                        'is_active' => (bool) $schedule->is_active,
                        'participants' => $schedule->participants->map(fn (SessionParticipant $participant) => [
                            'id' => $participant->id,
                            'name' => $participant->user?->name ?? 'Member',
                            'session_date' => $participant->session_date?->toDateString(),
                            'status' => $participant->status,
                        ])->values(),
                    ])->values(),
                ])->values(),
                'booking_time_slots' => BookingTimeSlot::query()
                    ->where('facility_id', $facility->id)
                    ->orderBy('start_time')
                    ->get()
                    ->map(fn (BookingTimeSlot $slot) => [
                        'id' => $slot->id,
                        'day' => $slot->day,
                        'start_time' => substr((string) $slot->start_time, 0, 5),
                        'end_time' => substr((string) $slot->end_time, 0, 5),
                        'price' => (float) $slot->price,
                        'is_active' => (bool) $slot->is_active,
                    ])->values(),
                'devices' => $facility->devices->map(fn ($device) => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'type' => $device->type,
                    'device_code' => $device->device_code,
                    'mqtt_topic' => $device->mqtt_topic,
                    'court_id' => $device->court_id,
                    'court_name' => $device->court?->name,
                    'status' => $device->status,
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_bookable_online'] = (bool) ($data['is_bookable_online'] ?? false);
        $data['has_membership'] = (bool) ($data['has_membership'] ?? false);
        $data['has_booking'] = (bool) ($data['has_booking'] ?? false);
        $data['has_session'] = (bool) ($data['has_session'] ?? false);

        Facility::create($data);

        return back()->with('success', 'Olahraga berhasil dibuat.');
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->uniqueSlug($data['name'], $facility->id);
        $data['is_bookable_online'] = (bool) ($data['is_bookable_online'] ?? false);
        $data['has_membership'] = (bool) ($data['has_membership'] ?? false);
        $data['has_booking'] = (bool) ($data['has_booking'] ?? false);
        $data['has_session'] = (bool) ($data['has_session'] ?? false);

        $facility->update($data);

        return back()->with('success', 'Olahraga berhasil diperbarui.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        abort_if(
            $facility->courts()->exists() || $facility->packages()->exists(),
            422,
            'Olahraga tidak dapat dihapus karena masih memiliki lapangan atau paket.',
        );

        $facility->delete();

        return back()->with('success', 'Olahraga berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'inactive'])],
            'is_bookable_online' => ['boolean'],
            'has_membership' => ['boolean'],
            'has_booking' => ['boolean'],
            'has_session' => ['boolean'],
            'turnaround_minutes' => ['required', 'integer', 'min:0', 'max:120'],
        ];
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'facility';
        $slug = $base;
        $suffix = 1;

        while (Facility::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}
