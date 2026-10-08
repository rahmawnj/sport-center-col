<?php

namespace App\Http\Controllers;

use App\Models\Facility;
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
        ]);

        return Inertia::render('facilities/Show', [
            'facility' => [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
                'is_bookable_online' => (bool) $facility->is_bookable_online,
                'turnaround_minutes' => $facility->turnaround_minutes,
                'status' => $facility->status,
                'created_at' => $facility->created_at?->toISOString(),
                'courts' => $facility->courts->map(fn ($court) => [
                    'id' => $court->id,
                    'name' => $court->name,
                    'status' => $court->status,
                ])->values(),
                'packages' => $facility->packages->map(fn ($package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'type' => $package->type,
                    'duration_minutes' => $package->duration_minutes,
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
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_bookable_online'] = (bool) ($data['is_bookable_online'] ?? false);

        Facility::create($data);

        return back()->with('success', 'Fasilitas berhasil dibuat.');
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->uniqueSlug($data['name'], $facility->id);
        $data['is_bookable_online'] = (bool) ($data['is_bookable_online'] ?? false);

        $facility->update($data);

        return back()->with('success', 'Fasilitas berhasil diperbarui.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        abort_if(
            $facility->courts()->exists() || $facility->packages()->exists(),
            422,
            'Fasilitas tidak dapat dihapus karena masih memiliki lapangan atau paket.',
        );

        $facility->delete();

        return back()->with('success', 'Fasilitas berhasil dihapus.');
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
