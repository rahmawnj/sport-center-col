<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Zone;
use App\Models\ZoneSpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ZoneSpaceController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function index(Request $request): Response
    {
        $this->authorizeAdmin();

        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');
        $zoneId = $request->input('zone_id', 'all');

        $spaces = ZoneSpace::query()
            ->with(['zone:id,name', 'facilities:id,name'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($zoneId !== 'all' && is_numeric($zoneId), fn ($query) => $query->where('zone_id', (int) $zoneId))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ZoneSpace $space) => [
                'id' => $space->id,
                'zone_id' => $space->zone_id,
                'zone_name' => $space->zone?->name,
                'name' => $space->name,
                'capacity' => $space->capacity,
                'status' => $space->status,
                'facilities' => $space->facilities->map(fn (Facility $facility) => [
                    'id' => $facility->id,
                    'name' => $facility->name,
                ])->values()->all(),
                'created_at' => $space->created_at?->toISOString(),
            ]);

        return Inertia::render('zone-spaces/Index', [
            'spaces' => $spaces,
            'zones' => Zone::query()->orderBy('name')->get(['id', 'name']),
            'facilities' => Facility::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search, 'status' => $status, 'zone_id' => $zoneId],
            'stats' => [
                'total' => ZoneSpace::count(),
                'available' => ZoneSpace::where('status', 'available')->count(),
                'maintenance' => ZoneSpace::where('status', 'maintenance')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:available,maintenance'],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['integer', 'exists:facilities,id'],
        ]);

        $facilityIds = $data['facility_ids'] ?? [];
        unset($data['facility_ids']);

        $zoneSpace = ZoneSpace::create($data);
        $zoneSpace->facilities()->sync($facilityIds);

        return back()->with('success', 'Zone space berhasil dibuat.');
    }

    public function update(Request $request, ZoneSpace $zoneSpace): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:available,maintenance'],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['integer', 'exists:facilities,id'],
        ]);

        $facilityIds = $data['facility_ids'] ?? [];
        unset($data['facility_ids']);

        $zoneSpace->update($data);
        $zoneSpace->facilities()->sync($facilityIds);

        return back()->with('success', 'Zone space berhasil diperbarui.');
    }

    public function destroy(ZoneSpace $zoneSpace): RedirectResponse
    {
        $this->authorizeAdmin();
        $zoneSpace->delete();
        return back()->with('success', 'Zone space berhasil dihapus.');
    }
}
