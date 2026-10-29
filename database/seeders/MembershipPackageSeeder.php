<?php

namespace Database\Seeders;

use App\Models\MembershipPackage;
use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MembershipPackageSeeder extends Seeder
{
    /**
     * Build the membership catalog from existing bookable packages marked
     * as "membership". This seeder does not modify schema or migrations.
     */
    public function run(): void
    {
        if (! Schema::hasTable('membership_packages') || ! Schema::hasColumn('membership_packages', 'session_quota')) {
            $this->command?->warn('Membership packages were skipped because the expected table/column is missing.');

            return;
        }

        DB::transaction(function (): void {
            Package::query()
                ->with(['facility', 'pricingRules' => fn ($query) => $query
                    ->orderByDesc('priority')
                    ->orderBy('price')])
                ->where('type', 'membership')
                ->whereNotNull('duration_days')
                ->orderBy('id')
                ->get()
                ->each(function (Package $package): void {
                    $priceRule = $package->pricingRules->first();

                    if (! $priceRule) {
                        $this->command?->warn("Skipped membership package '{$package->name}' because it has no pricing rule.");

                        return;
                    }

                    preg_match('/(\d+)\s*sesi/i', $package->name, $matches);
                    $sessionQuota = isset($matches[1]) ? (int) $matches[1] : null;
                    $facilityName = $package->facility?->name;

                    $description = trim(implode(' - ', array_filter([
                        $facilityName,
                        $sessionQuota
                            ? "Kuota {$sessionQuota} sesi selama {$package->duration_days} hari"
                            : "Akses membership selama {$package->duration_days} hari",
                    ])));

                    MembershipPackage::withTrashed()->updateOrCreate(
                        ['name' => $package->name],
                        [
                            'price' => $priceRule->price,
                            'duration_days' => $package->duration_days,
                            'session_quota' => $sessionQuota,
                            'description' => $description,
                            'deleted_at' => null,
                        ]
                    );
                });
        });
    }
}
