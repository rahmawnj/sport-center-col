<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    public function index(Request $request): Response
    {
        $zones = Zone::query()
            ->where('is_online_bookable', true)
            ->with(['zoneSpaces' => fn ($query) => $query
                ->where('status', 'available')
                ->with(['facilities:id,name'])
                ->with(['pricingRates' => fn ($rateQuery) => $rateQuery->select('id', 'zone_space_id', 'rental_type', 'price')])
                ->orderBy('name')
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'pricing_model', 'is_online_bookable']);

        return Inertia::render('booking/Index', [
            'zones' => $zones,
            'initial' => [
                'date' => $request->input('date'),
                'zone_id' => $request->input('zone_id'),
            ],
        ]);
    }
}
