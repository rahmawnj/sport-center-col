<?php

use App\Models\Facility;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeFacility(): Facility
{
    return Facility::create([
        'name' => 'GOR',
        'slug' => 'gor',
        'is_bookable_online' => true,
        'turnaround_minutes' => 5,
        'status' => 'active',
    ]);
}

it('creates a no-duration entry package', function () {
    $user = User::factory()->create();
    $facility = makeFacility();

    $this->actingAs($user)->post("/facilities/{$facility->id}/packages", [
        'name' => 'Gym 1x Visit',
        'type' => 'entry',
        'pricing_rules' => [
            ['day_type' => 'all', 'price' => 30000],
        ],
    ])->assertRedirect();

    $package = Package::sole();

    expect($package->duration_minutes)->toBe(0);
});

it('requires a duration for session packages', function () {
    $user = User::factory()->create();
    $facility = makeFacility();

    $this->actingAs($user)->post("/facilities/{$facility->id}/packages", [
        'name' => 'Padel 1 Jam',
        'type' => 'session',
        'pricing_rules' => [
            ['day_type' => 'all', 'price' => 100000],
        ],
    ])->assertSessionHasErrors('duration_minutes');

    expect(Package::count())->toBe(0);
});
