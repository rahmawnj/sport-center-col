<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ZoneController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function index(Request $request): Response
    {
        $this->authorizeAdmin();

        $search = trim((string) $request->input('search', ''));
        $online = $request->input('online', 'all');

        $zones = Zone::query()
            ->withCount('zoneSpaces')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($online === 'online', fn ($query) => $query->where('is_online_bookable', true))
            ->when($online === 'offline', fn ($query) => $query->where('is_online_bookable', false))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Zone $zone) => [
                'id' => $zone->id,
                'name' => $zone->name,
                'pricing_model' => $zone->pricing_model,
                'is_online_bookable' => (bool) $zone->is_online_bookable,
                'zone_spaces_count' => $zone->zone_spaces_count,
                'created_at' => $zone->created_at?->toISOString(),
            ]);

        return Inertia::render('zones/Index', [
            'zones' => $zones,
            'filters' => ['search' => $search, 'online' => $online],
            'stats' => [
                'total' => Zone::count(),
                'online' => Zone::where('is_online_bookable', true)->count(),
                'offline' => Zone::where('is_online_bookable', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:zones,name'],
            'pricing_model' => ['required', 'in:per_person,per_space,per_trainer_session,per_table'],
            'is_online_bookable' => ['required', 'boolean'],
        ]);

        Zone::create($data);
        return back()->with('success', 'Zone berhasil dibuat.');
    }

    public function update(Request $request, Zone $zone): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:zones,name,' . $zone->id],
            'pricing_model' => ['required', 'in:per_person,per_space,per_trainer_session,per_table'],
            'is_online_bookable' => ['required', 'boolean'],
        ]);

        $zone->update($data);
        return back()->with('success', 'Zone berhasil diperbarui.');
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_if($zone->zoneSpaces()->exists(), 422, 'Zone tidak dapat dihapus karena masih memiliki zone space.');
        $zone->delete();
        return back()->with('success', 'Zone berhasil dihapus.');
    }
}
