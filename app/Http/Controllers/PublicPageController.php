<?php

namespace App\Http\Controllers;

use App\Models\AddOn;
use App\Models\Trainer;
use App\Models\Zone;
use App\Models\ZoneSpace;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PublicPageController extends Controller
{
    public function sitemap(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $zones = Zone::query()->orderBy('updated_at', 'desc')->get(['id', 'updated_at']);

        $urls = [
            ['loc' => $baseUrl.'/', 'changefreq' => 'daily', 'priority' => '1.0'],
        ];

        foreach ($zones as $zone) {
            $urls[] = [
                'loc' => $baseUrl.'/zones/'.$zone->id,
                'lastmod' => optional($zone->updated_at)->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $item) {
            $xml .= '<url><loc>'.e($item['loc']).'</loc>';

            if (!empty($item['lastmod'])) {
                $xml .= '<lastmod>'.e($item['lastmod']).'</lastmod>';
            }

            $xml .= '<changefreq>'.e($item['changefreq']).'</changefreq>';
            $xml .= '<priority>'.e($item['priority']).'</priority></url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function home(): InertiaResponse
    {
        $zones = Zone::query()
            ->with(['zoneSpaces' => fn ($query) => $query->whereNull('deleted_at')->with(['pricingRates', 'facilities'])])
            ->orderBy('name')
            ->get()
            ->map(fn (Zone $zone) => [
                'id' => $zone->id,
                'name' => $zone->name,
                'pricing_model' => $zone->pricing_model,
                'is_online_bookable' => $zone->is_online_bookable,
                'spaces' => $zone->zoneSpaces->map(fn (ZoneSpace $space) => [
                    'id' => $space->id,
                    'name' => $space->name,
                    'capacity' => $space->capacity,
                    'status' => $space->status,
                    'facilities' => $space->facilities->pluck('name')->values(),
                    'starting_price' => $space->pricingRates->min('price'),
                ])->values(),
                'starting_price' => $zone->zoneSpaces
                    ->flatMap(fn (ZoneSpace $space) => $space->pricingRates)
                    ->min('price'),
            ]);

        $trainers = Trainer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'specialty']);

        $addOns = AddOn::query()
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock']);

        return Inertia::render('Welcome', [
            'zones' => $zones,
            'trainers' => $trainers,
            'addOns' => $addOns,
        ]);
    }

    public function showZone(Zone $zone): InertiaResponse
    {
        $zone->load(['zoneSpaces' => fn ($query) => $query
            ->whereNull('deleted_at')
            ->with(['pricingRates', 'facilities'])]);

        return Inertia::render('ZoneShow', [
            'zone' => [
                'id' => $zone->id,
                'name' => $zone->name,
                'pricing_model' => $zone->pricing_model,
                'is_online_bookable' => $zone->is_online_bookable,
                'spaces' => $zone->zoneSpaces->map(fn (ZoneSpace $space) => [
                    'id' => $space->id,
                    'name' => $space->name,
                    'capacity' => $space->capacity,
                    'status' => $space->status,
                    'facilities' => $space->facilities->pluck('name')->values(),
                    'pricing_rates' => $space->pricingRates->map(fn ($rate) => [
                        'rental_type' => $rate->rental_type,
                        'price' => $rate->price,
                        'unit_type' => $rate->unit_type,
                        'min_booking_duration' => $rate->min_booking_duration,
                    ])->values(),
                ])->values(),
            ],
        ]);
    }
}
