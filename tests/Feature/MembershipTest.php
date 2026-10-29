<?php

use App\Models\Facility;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function makeMembershipPackage(): Package
{
    $facility = Facility::create([
        'name' => 'Gym',
        'slug' => 'gym',
        'is_bookable_online' => true,
        'turnaround_minutes' => 0,
        'status' => 'active',
    ]);

    $package = Package::create([
        'facility_id' => $facility->id,
        'name' => 'Membership 1 Bulan',
        'type' => 'membership',
        'duration_days' => 30,
    ]);

    $package->pricingRules()->create([
        'day_type' => 'all',
        'price' => 300000,
    ]);

    return $package;
}

it('creates a membership when sold from the cashier', function () {
    $staff = User::factory()->create();
    $member = User::factory()->create();
    $package = makeMembershipPackage();

    $this->actingAs($staff)->post('/transactions', [
        'package_id' => $package->id,
        'user_id' => $member->id,
        'quantity' => 1,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'amount' => 300000,
    ])->assertRedirect(route('transactions.index'));

    $membership = UserMembership::sole();

    expect($membership->user_id)->toBe($member->id);
    expect($membership->status)->toBe('active');
    expect($membership->end_date->toDateString())
        ->toBe(Carbon::today()->addDays(30)->toDateString());
    expect($membership->transactions()->count())->toBe(1);
});

it('renews a membership by extending from its end date', function () {
    $staff = User::factory()->create();
    $member = User::factory()->create();
    $package = makeMembershipPackage();

    $membership = UserMembership::create([
        'user_id' => $member->id,
        'package_id' => $package->id,
        'start_date' => Carbon::today()->toDateString(),
        'end_date' => Carbon::today()->addDays(10)->toDateString(),
        'status' => 'active',
    ]);

    $this->actingAs($staff)
        ->post("/memberships/{$membership->id}/renew", [
            'payment_method' => 'cash',
        ])
        ->assertRedirect();

    $membership->refresh();

    expect($membership->end_date->toDateString())
        ->toBe(Carbon::today()->addDays(40)->toDateString());
    expect(Transaction::where('transactionable_type', UserMembership::class)->count())->toBe(1);
});

it('shows the membership detail with the member qr code', function () {
    $staff = User::factory()->create();
    $member = User::factory()->create();
    $member->memberProfile()->create(['phone' => '0812']);
    $package = makeMembershipPackage();

    $membership = UserMembership::create([
        'user_id' => $member->id,
        'package_id' => $package->id,
        'start_date' => Carbon::today()->toDateString(),
        'end_date' => Carbon::today()->addDays(30)->toDateString(),
        'status' => 'active',
    ]);

    $this->actingAs($staff)
        ->get("/memberships/{$membership->id}")
        ->assertOk();
});
