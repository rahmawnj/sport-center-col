<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Resource;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SportController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');

        $sports = Sport::query()
            ->withCount('packages')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Sport $sport) => [
                'id' => $sport->id,
                'name' => $sport->name,
                'slug' => $sport->slug,
                'description' => $sport->description,
                'thumbnail' => $sport->thumbnail,
                'is_active' => (bool) $sport->is_active,
                'is_online' => (bool) $sport->is_online,
                'sort_order' => $sport->sort_order,
                'packages_count' => $sport->packages_count,
                'created_at' => $sport->created_at?->toISOString(),
            ]);

        return Inertia::render('sports/Index', [
            'sports' => $sports,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'total' => Sport::count(),
                'active' => Sport::where('is_active', true)->count(),
                'online' => Sport::where('is_online', true)->count(),
            ],
        ]);
    }

    public function show(Sport $sport): Response
    {
        $sport->loadCount(['packages', 'resources']);

        return Inertia::render('sports/Show', [
            'sport' => [
                'id' => $sport->id,
                'name' => $sport->name,
                'slug' => $sport->slug,
                'description' => $sport->description,
                'thumbnail' => $sport->thumbnail,
                'is_active' => (bool) $sport->is_active,
                'is_online' => (bool) $sport->is_online,
                'sort_order' => $sport->sort_order,
                'packages_count' => $sport->packages_count,
                'resources_count' => $sport->resources_count,
                'created_at' => $sport->created_at?->toISOString(),
                'packages' => $sport->packages()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Package $package) => [
                        'id' => $package->id,
                        'name' => $package->name,
                        'description' => $package->description,
                        'pricing_type' => $package->pricing_type,
                        'price' => (float) $package->price,
                        'duration_value' => $package->duration_value,
                        'duration_unit' => $package->duration_unit,
                        'session_count' => $package->session_count,
                        'is_promo' => (bool) $package->is_promo,
                        'is_active' => (bool) $package->is_active,
                    ]),
                'resources' => $sport->resources()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Resource $resource) => [
                        'id' => $resource->id,
                        'name' => $resource->name,
                        'capacity' => $resource->capacity,
                        'is_active' => (bool) $resource->is_active,
                    ]),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:sports,name'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_online' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Sport::create($data);

        return back()->with('success', 'Olahraga berhasil dibuat.');
    }

    public function update(Request $request, Sport $sport): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:sports,name,'.$sport->id],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_online' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $sport->update($data);

        return back()->with('success', 'Olahraga berhasil diperbarui.');
    }

    public function destroy(Sport $sport): RedirectResponse
    {
        abort_if($sport->packages()->exists(), 422, 'Olahraga tidak dapat dihapus karena masih memiliki paket.');

        $sport->delete();

        return back()->with('success', 'Olahraga berhasil dihapus.');
    }
}
