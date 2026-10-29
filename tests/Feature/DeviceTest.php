<?php

use App\Models\Device;
use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates, updates and deletes a device for a facility', function () {
    $adminRole = Role::firstOrCreate(['name' => 'Superadmin']);
    $user = User::factory()->create(['role_id' => $adminRole->id]);

    $facility = Facility::create([
        'name' => 'Gym',
        'slug' => 'gym',
        'is_bookable_online' => true,
        'turnaround_minutes' => 5,
        'status' => 'active',
    ]);

    $this->actingAs($user)->post("/facilities/{$facility->id}/devices", [
        'name' => 'Sensor Pintu',
        'type' => 'RFID Reader',
        'device_code' => 'SN-001',
        'status' => 'active',
    ])->assertRedirect();

    $device = Device::sole();

    expect($device->name)->toBe('Sensor Pintu');
    expect($device->facility_id)->toBe($facility->id);

    $this->actingAs($user)->put("/devices/{$device->id}", [
        'name' => 'Sensor Pintu (update)',
        'status' => 'maintenance',
    ])->assertRedirect();

    expect($device->fresh()->name)->toBe('Sensor Pintu (update)');
    expect($device->fresh()->status)->toBe('maintenance');

    $this->actingAs($user)->delete("/devices/{$device->id}")->assertRedirect();

    expect(Device::count())->toBe(0);
});
