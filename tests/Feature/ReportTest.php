<?php

use App\Models\Facility;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the package sales report', function () {
    $user = User::factory()->create();

    $facility = Facility::create([
        'name' => 'Gym',
        'slug' => 'gym',
        'is_bookable_online' => true,
        'turnaround_minutes' => 0,
        'status' => 'active',
    ]);

    $package = Package::create([
        'facility_id' => $facility->id,
        'name' => 'Tiket Harian',
        'type' => 'entry',
        'duration_minutes' => 0,
    ]);

    $package->transactions()->create([
        'amount' => 50000,
        'payment_status' => 'paid',
    ]);

    $this->actingAs($user)
        ->get('/reports/package')
        ->assertOk();

    $this->actingAs($user)
        ->get('/reports/booking')
        ->assertOk();
});
