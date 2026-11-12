<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackagePricingRule;
use Illuminate\Database\Seeder;

class BookingPricingSeeder extends Seeder
{
    /**
     * Apply sample time-based weekday/weekend prices to all non-membership
     * booking packages. Each sport keeps its own base price.
     *
     * Existing special promo rules (priority >= 100) are preserved.
     */
    public function run(): void
    {
        Package::query()
            ->whereIn('type', ['session', 'entry'])
            ->with('pricingRules')
            ->get()
            ->each(function (Package $package): void {
                // BookingLandingPageSeeder already defines zone-specific tariffs.
                // Preserve those rules instead of replacing them with generic bands.
                if ($package->pricingRules->isNotEmpty()) {
                    return;
                }

                $basePrice = (float) ($package->pricingRules->where('priority', '<', 100)->min('price') ?? $package->price);

                if ($basePrice <= 0) {
                    $this->command?->warn("Tarif {$package->name} dilewati karena belum memiliki harga dasar.");
                    return;
                }

                PackagePricingRule::query()
                    ->where('package_id', $package->id)
                    ->where('priority', '<', 100)
                    ->delete();

                $bands = [
                    ['07:00:00', '12:00:00', 1.00, 1.20],
                    ['12:00:00', '17:00:00', 1.20, 1.40],
                    ['17:00:00', '23:00:00', 1.50, 1.70],
                ];

                foreach ($bands as [$start, $end, $weekdayMultiplier, $weekendMultiplier]) {
                    foreach ([
                        'weekday' => $weekdayMultiplier,
                        'weekend' => $weekendMultiplier,
                    ] as $dayType => $multiplier) {
                        $price = (int) (round(($basePrice * $multiplier) / 5000) * 5000);

                        PackagePricingRule::updateOrCreate(
                            [
                                'package_id' => $package->id,
                                'day_type' => $dayType,
                                'start_time' => $start,
                                'end_time' => $end,
                                'priority' => 10,
                            ],
                            [
                                'date_start' => null,
                                'date_end' => null,
                                'price' => $price,
                            ],
                        );
                    }
                }
            });
    }
}
