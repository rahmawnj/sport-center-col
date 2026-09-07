<?php

namespace App\Http\Controllers;

use App\Models\PricingRate;
use App\Models\ZoneSpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PricingRateController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function index(Request $request): Response
    {
        $this->authorizeAdmin();

        $search = trim((string) $request->input('search', ''));
        $zoneSpaceId = $request->input('zone_space_id', 'all');

        $rates = PricingRate::query()
            ->with('zoneSpace:id,zone_id,name')
            ->when($search !== '', fn ($query) => $query->where('rental_type', 'like', "%{$search}%")
                ->orWhereHas('zoneSpace', fn ($spaceQuery) => $spaceQuery->where('name', 'like', "%{$search}%")))
            ->when($zoneSpaceId !== 'all' && is_numeric($zoneSpaceId), fn ($query) => $query->where('zone_space_id', (int) $zoneSpaceId))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (PricingRate $rate) => [
                'id' => $rate->id,
                'zone_space_id' => $rate->zone_space_id,
                'zone_space_name' => $rate->zoneSpace?->name,
                'rental_type' => $rate->rental_type,
                'price' => $rate->price,
                'created_at' => $rate->created_at?->toISOString(),
            ]);

        return Inertia::render('pricing-rates/Index', [
            'rates' => $rates,
            'zoneSpaces' => ZoneSpace::query()->with('zone:id,name')->orderBy('name')->get(['id', 'zone_id', 'name']),
            'filters' => ['search' => $search, 'zone_space_id' => $zoneSpaceId],
            'stats' => [
                'total' => PricingRate::count(),
                'zoneSpaces' => PricingRate::query()->distinct('zone_space_id')->count('zone_space_id'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'zone_space_id' => ['required', 'integer', 'exists:zone_spaces,id'],
            'rental_type' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        PricingRate::create($data);
        return back()->with('success', 'Pricing rate berhasil dibuat.');
    }

    public function update(Request $request, PricingRate $pricingRate): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'zone_space_id' => ['required', 'integer', 'exists:zone_spaces,id'],
            'rental_type' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $pricingRate->update($data);
        return back()->with('success', 'Pricing rate berhasil diperbarui.');
    }

    public function destroy(PricingRate $pricingRate): RedirectResponse
    {
        $this->authorizeAdmin();
        $pricingRate->delete();
        return back()->with('success', 'Pricing rate berhasil dihapus.');
    }
}
