<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $sportId = $request->input('sport_id', 'all');
        $status = $request->input('status', 'all');

        $resources = Resource::query()
            ->with('sport:id,name')
            ->withCount('bookings')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($sportId !== 'all' && is_numeric($sportId), fn ($query) => $query->where('sport_id', (int) $sportId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Resource $resource) => [
                'id' => $resource->id,
                'sport_id' => $resource->sport_id,
                'sport_name' => $resource->sport?->name,
                'name' => $resource->name,
                'capacity' => $resource->capacity,
                'is_active' => (bool) $resource->is_active,
                'bookings_count' => $resource->bookings_count,
                'created_at' => $resource->created_at?->toISOString(),
            ]);

        return Inertia::render('resources/Index', [
            'resources' => $resources,
            'sports' => Sport::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search, 'sport_id' => $sportId, 'status' => $status],
            'stats' => [
                'total' => Resource::count(),
                'active' => Resource::where('is_active', true)->count(),
                'inactive' => Resource::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('resources/Create', [
            'sports' => Sport::query()->orderBy('name')->get(['id', 'name']),
            'defaultSportId' => $request->integer('sport_id') ?: null,
        ]);
    }

    public function edit(Resource $resource): Response
    {
        return Inertia::render('resources/Create', [
            'sports' => Sport::query()->orderBy('name')->get(['id', 'name']),
            'defaultSportId' => $resource->sport_id,
            'resource' => [
                'id' => $resource->id,
                'sport_id' => $resource->sport_id,
                'name' => $resource->name,
                'capacity' => $resource->capacity !== null ? (string) $resource->capacity : '',
                'is_active' => $resource->is_active ? '1' : '0',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        Resource::create($data);

        return redirect()
            ->route('sports.show', $data['sport_id'])
            ->with('success', 'Lapangan berhasil dibuat.');
    }

    public function update(Request $request, Resource $resource): RedirectResponse
    {
        $resource->update($request->validate($this->rules()));

        return redirect()
            ->route('sports.show', $resource->sport_id)
            ->with('success', 'Lapangan berhasil diperbarui.');
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        abort_if($resource->bookings()->exists(), 422, 'Resource tidak dapat dihapus karena sudah memiliki booking.');

        $resource->delete();

        return back()->with('success', 'Resource berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
