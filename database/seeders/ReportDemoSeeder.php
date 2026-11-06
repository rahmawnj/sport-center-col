<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportDemoSeeder extends Seeder
{
    /**
     * Create repeatable demo transactions for the three report categories.
     * Existing demo invoice numbers are skipped, so running this seeder again
     * will not create duplicate rows.
     */
    public function run(): void
    {
        $members = User::query()
            ->whereIn('email', [
                'member@example.com',
                'andi.pratama@example.com',
                'siti.rahma@example.com',
                'budi.santoso@example.com',
            ])
            ->orderBy('id')
            ->get();

        if ($members->count() < 3) {
            $this->command?->warn('Report demo data skipped: run DatabaseSeeder first to create demo member accounts.');

            return;
        }

        $membershipPackages = Package::query()
            ->where('type', 'membership')
            ->whereNotNull('duration_days')
            ->orderBy('id')
            ->get();

        $bookingPackages = Package::query()
            ->where('type', 'session')
            ->orderBy('id')
            ->get();

        $salePackages = Package::query()
            ->where('type', 'entry')
            ->orderBy('id')
            ->get();

        if ($membershipPackages->isEmpty() || $bookingPackages->isEmpty() || $salePackages->isEmpty()) {
            $this->command?->warn('Report demo data skipped: membership, session, and entry packages are required. Run the catalog seeders first.');

            return;
        }

        DB::transaction(function () use ($members, $membershipPackages, $bookingPackages, $salePackages): void {
            $samples = [
                ['days_ago' => 2, 'status' => 'fully_paid', 'method' => 'cash'],
                ['days_ago' => 5, 'status' => 'unpaid', 'method' => 'bank_transfer'],
                ['days_ago' => 9, 'status' => 'dp_paid', 'method' => 'cash'],
            ];

            foreach ($samples as $index => $sample) {
                $member = $members[$index];
                $createdAt = Carbon::now()->subDays($sample['days_ago'])->setTime(10 + $index, 15);

                // Membership purchase
                $membershipInvoice = sprintf('RPT-MEM-%03d', $index + 1);

                if (! Transaction::query()->where('invoice_number', $membershipInvoice)->exists()) {
                    $package = $membershipPackages[$index % $membershipPackages->count()];
                    $startDate = $createdAt->toDateString();
                    $endDate = $createdAt->copy()->addDays(max(1, (int) $package->duration_days))->toDateString();

                    $membership = UserMembership::query()->create([
                        'user_id' => $member->id,
                        'package_id' => $package->id,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'status' => 'active',
                    ]);

                    Transaction::query()->create([
                        'invoice_number' => $membershipInvoice,
                        'transactionable_type' => 'membership',
                        'user_id' => $member->id,
                        'guest_name' => $member->name,
                        'total_amount' => $this->packagePrice($package),
                        'payment_method' => $sample['method'],
                        'payment_status' => $sample['status'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                // Court booking
                $bookingInvoice = sprintf('RPT-BKG-%03d', $index + 1);

                if (! Transaction::query()->where('invoice_number', $bookingInvoice)->exists()) {
                    $package = $bookingPackages[$index % $bookingPackages->count()];
                    $court = Court::query()
                        ->where('facility_id', $package->facility_id)
                        ->where('status', 'available')
                        ->orderBy('id')
                        ->first();

                    if (! $court) {
                        throw new \RuntimeException("No available court found for package {$package->name}.");
                    }

                    $bookingDate = $createdAt->copy()->addDays(1);
                    $booking = Booking::query()->create([
                        'user_id' => $member->id,
                        'guest_name' => $member->name,
                        'guest_email' => $member->email,
                        'guest_phone' => $member->phone,
                        'court_id' => $court->id,
                        'date' => $bookingDate->toDateString(),
                        'start_time' => sprintf('%02d:00:00', 9 + $index),
                        'end_time' => sprintf('%02d:00:00', 10 + $index),
                        'status' => 'confirmed',
                    ]);

                    Transaction::query()->create([
                        'invoice_number' => $bookingInvoice,
                        'transactionable_type' => 'booking',
                        'user_id' => $member->id,
                        'guest_name' => $member->name,
                        'total_amount' => $this->packagePrice($package),
                        'payment_method' => $sample['method'],
                        'payment_status' => $sample['status'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                // Direct package sale (entry package)
                $packageInvoice = sprintf('RPT-PKG-%03d', $index + 1);

                if (! Transaction::query()->where('invoice_number', $packageInvoice)->exists()) {
                    $package = $salePackages[$index % $salePackages->count()];

                    Transaction::query()->create([
                        'invoice_number' => $packageInvoice,
                        'transactionable_type' => 'booking',
                        'user_id' => $member->id,
                        'guest_name' => $member->name,
                        'total_amount' => $this->packagePrice($package),
                        'payment_method' => $sample['method'],
                        'payment_status' => $sample['status'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }
        });

        $this->command?->info('Report demo data created: 3 membership, 3 booking, and 3 package transactions (existing demo invoices were skipped).');
    }

    private function packagePrice(Package $package): float
    {
        return (float) ($package->pricingRules()->orderByDesc('priority')->value('price') ?? 0);
    }
}
