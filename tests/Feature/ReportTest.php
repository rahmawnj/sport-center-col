<?php

use App\Models\Facility;
use App\Models\MemberProfile;
use App\Models\Package;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function reportAdmin(): User
{
    $role = Role::firstOrCreate(['name' => 'Superadmin']);

    return User::factory()->create(['role_id' => $role->id]);
}

it('shows all four purchase report pages and removes the old package report', function () {
    $admin = reportAdmin();

    foreach (['membership', 'trainer', 'booking', 'class'] as $type) {
        $this->actingAs($admin)
            ->get("/reports/{$type}")
            ->assertOk();
    }

    $this->actingAs($admin)
        ->get('/reports/package')
        ->assertNotFound();
});

it('shows member identity and membership purchase value in the membership report', function () {
    $admin = reportAdmin();
    $member = User::factory()->create();

    $profile = MemberProfile::query()->create([
        'user_id' => $member->id,
        'member_code' => 'MEM-REPORT-001',
        'phone' => '081234567890',
    ]);

    $facility = Facility::create([
        'name' => 'Gym',
        'slug' => 'gym',
        'is_bookable_online' => true,
        'turnaround_minutes' => 0,
        'status' => 'active',
    ]);

    $package = Package::create([
        'facility_id' => $facility->id,
        'name' => 'Membership Bulanan',
        'type' => 'membership',
        'duration_minutes' => 0,
        'duration_days' => 30,
        'price' => 150000,
    ]);

    $transaction = Transaction::query()->create([
        'transactionable_type' => 'membership',
        'user_id' => $member->id,
        'guest_name' => $member->name,
        'total_amount' => 150000,
        'payment_method' => 'bank_transfer',
        'payment_status' => 'fully_paid',
    ]);

    UserMembership::query()->create([
        'user_id' => $member->id,
        'transaction_id' => $transaction->id,
        'package_id' => $package->id,
        'start_date' => today()->toDateString(),
        'end_date' => today()->addDays(29)->toDateString(),
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get('/reports/membership')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/Index')
            ->where('type', 'membership')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.customer_name', $member->name)
            ->where('transactions.data.0.member_code', $profile->member_code)
            ->where('transactions.data.0.package_name', $package->name)
            ->where('transactions.data.0.amount', 150000)
            ->where('summary.count', 1)
            ->where('summary.paid', 150000)
        );
});
