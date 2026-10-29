<?php

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a member with a profile', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)->post('/members', [
        'name' => 'Andi',
        'email' => 'andi@example.com',
        'password' => 'password123',
        'member_code' => 'MBR-001',
        'phone' => '08123456789',
        'date_of_birth' => '2000-01-01',
        'gender' => 'male',
    ])->assertRedirect();

    $member = User::where('email', 'andi@example.com')->sole();

    expect($member->memberProfile->member_code)->toBe('MBR-001');
    expect(MemberProfile::count())->toBe(1);
});

it('auto-generates member code and qr code when omitted', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff)->post('/members', [
        'name' => 'Rina',
        'email' => 'rina@example.com',
        'password' => 'password123',
        'phone' => '08123456789',
    ])->assertRedirect();

    $profile = User::where('email', 'rina@example.com')->sole()->memberProfile;

    expect($profile->member_code)->toMatch('/^MEM[A-Z0-9]{4}\d{7}$/');
    expect($profile->qr_code)->toMatch('/^[A-Z0-9]{8}\d{16}$/');
});
