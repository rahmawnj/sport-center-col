<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\Device;
use App\Models\Facility;
use App\Models\Package;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

function makeBookablePackage(): array
{
    $facility = Facility::create([
        'name' => 'Padel Hall',
        'slug' => 'padel-hall',
        'is_bookable_online' => true,
        'turnaround_minutes' => 0,
        'status' => 'active',
    ]);

    $court = Court::create([
        'facility_id' => $facility->id,
        'name' => 'Lapangan 1',
        'status' => 'available',
    ]);

    $package = Package::create([
        'facility_id' => $facility->id,
        'name' => 'Padel 1 Jam',
        'type' => 'session',
        'duration_minutes' => 60,
    ]);

    $package->pricingRules()->createMany([
        ['day_type' => 'weekday', 'start_time' => '09:00', 'end_time' => '16:00', 'price' => 250000],
        ['day_type' => 'weekday', 'start_time' => '16:00', 'end_time' => '23:00', 'price' => 300000],
    ]);

    return [$facility, $court, $package];
}

it('prices a weekday slot by its start hour', function () {
    [, , $package] = makeBookablePackage();
    $date = Carbon::parse('next monday')->toDateString();

    $response = $this->getJson("/book/availability?package_id={$package->id}&date={$date}");

    $response->assertOk();

    $slots = collect($response->json('slots'))->keyBy('start_time');

    expect($slots)->not->toBeEmpty();
    expect((float) $slots['09:00']['price'])->toBe(250000.0);
    expect((float) $slots['16:00']['price'])->toBe(300000.0);
    expect($slots['09:00']['end_time'])->toBe('10:00');
});

it('applies an active promo rule over the base rate', function () {
    [, , $package] = makeBookablePackage();
    $date = Carbon::parse('next monday');

    $package->pricingRules()->create([
        'day_type' => 'weekday',
        'date_start' => $date->copy()->subDay()->toDateString(),
        'date_end' => $date->copy()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '10:00',
        'price' => 100000,
        'priority' => 10,
    ]);

    $slots = collect($this->getJson("/book/availability?package_id={$package->id}&date={$date->toDateString()}")->json('slots'))
        ->keyBy('start_time');

    expect((float) $slots['09:00']['price'])->toBe(100000.0);
    expect((float) $slots['10:00']['price'])->toBe(250000.0);
});

it('ignores promo rules outside their date range', function () {
    [, , $package] = makeBookablePackage();
    $date = Carbon::parse('next monday');

    $package->pricingRules()->create([
        'day_type' => 'weekday',
        'date_start' => $date->copy()->addWeeks(2)->toDateString(),
        'date_end' => $date->copy()->addWeeks(3)->toDateString(),
        'start_time' => '09:00',
        'end_time' => '10:00',
        'price' => 100000,
        'priority' => 10,
    ]);

    $slots = collect($this->getJson("/book/availability?package_id={$package->id}&date={$date->toDateString()}")->json('slots'))
        ->keyBy('start_time');

    expect((float) $slots['09:00']['price'])->toBe(250000.0);
});

it('creates a booking and pending transaction on submit', function () {
    [, $court, $package] = makeBookablePackage();
    $date = Carbon::parse('next monday')->toDateString();

    $this->post('/book', [
        'package_id' => $package->id,
        'court_id' => $court->id,
        'date' => $date,
        'start_time' => '09:00',
        'guest_name' => 'Budi',
        'guest_email' => 'budi@example.com',
        'guest_phone' => '08123456789',
    ])->assertRedirect(route('booking.index'));

    $booking = Booking::sole();

    expect(substr($booking->end_time, 0, 5))->toBe('10:00');
    expect($booking->status)->toBe('pending');
    expect((float) $booking->transactions()->sole()->amount)->toBe(250000.0);
});

it('books a no-schedule package directly without a court', function () {
    $facility = Facility::create([
        'name' => 'Gym',
        'slug' => 'gym',
        'is_bookable_online' => true,
        'turnaround_minutes' => 0,
        'status' => 'active',
    ]);

    $package = Package::create([
        'facility_id' => $facility->id,
        'name' => 'Gym Harian',
        'type' => 'entry',
        'duration_minutes' => 0,
    ]);

    $package->pricingRules()->create([
        'day_type' => 'all',
        'start_time' => null,
        'end_time' => null,
        'price' => 50000,
    ]);

    $this->post('/book', [
        'package_id' => $package->id,
        'guest_name' => 'Citra',
        'guest_email' => 'citra@example.com',
        'guest_phone' => '08123456789',
    ])->assertRedirect(route('booking.index'));

    $transaction = Transaction::sole();

    expect((float) $transaction->amount)->toBe(50000.0);
    expect($transaction->transactionable_type)->toBe(Package::class);
    expect(Booking::count())->toBe(0);
});

it('binds a booking to the court device', function () {
    [$facility, $court, $package] = makeBookablePackage();
    $device = Device::create([
        'facility_id' => $facility->id,
        'court_id' => $court->id,
        'name' => 'Padel Court A Controller',
        'type' => 'controller',
        'device_code' => 'PADEL-TEST-01',
        'status' => 'active',
    ]);
    $date = Carbon::parse('next monday')->toDateString();

    $this->post('/book', [
        'package_id' => $package->id,
        'court_id' => $court->id,
        'date' => $date,
        'start_time' => '09:00',
        'duration_hours' => 1,
        'terms_accepted' => true,
        'guest_name' => 'Budi',
        'guest_email' => 'budi@example.com',
        'guest_phone' => '08123456789',
    ])->assertRedirect();

    expect(Booking::sole()->device_id)->toBe($device->id);
});

it('adds the court price adjustment to the booking total', function () {
    [, $court, $package] = makeBookablePackage();
    $court->update(['price_adjustment' => 25000]);
    $date = Carbon::parse('next monday')->toDateString();

    $this->post('/book', [
        'package_id' => $package->id,
        'court_id' => $court->id,
        'date' => $date,
        'start_time' => '09:00',
        'duration_hours' => 1,
        'terms_accepted' => true,
        'guest_name' => 'Budi',
        'guest_email' => 'budi@example.com',
        'guest_phone' => '08123456789',
    ])->assertRedirect();

    $booking = Booking::sole();
    $receipt = Cache::get('booking_receipt_'.$booking->id);

    expect((float) $receipt['total'])->toBe(275000.0);
});

it('spaces slots by the facility turnaround', function () {
    [, , $package] = makeBookablePackage();
    $package->facility->update(['turnaround_minutes' => 5]);
    $date = Carbon::parse('next monday')->toDateString();

    $slots = collect($this->getJson("/book/availability?package_id={$package->id}&date={$date}")->json('slots'))
        ->keyBy('start_time');

    expect($slots)->toHaveKey('09:00');
    expect($slots)->toHaveKey('10:05');
    expect($slots)->not->toHaveKey('10:00');
});

it('enforces the turnaround gap when checking overlaps', function () {
    [, $court, $package] = makeBookablePackage();
    $package->facility->update(['turnaround_minutes' => 5]);
    $date = Carbon::parse('next monday');

    Booking::create([
        'court_id' => $court->id,
        'date' => $date->toDateString(),
        'start_time' => '09:00',
        'end_time' => '10:00',
        'status' => 'confirmed',
    ]);

    $payload = [
        'package_id' => $package->id,
        'court_id' => $court->id,
        'date' => $date->toDateString(),
        'guest_name' => 'Budi',
        'guest_email' => 'budi@example.com',
        'guest_phone' => '08123456789',
    ];

    $this->post('/book', [...$payload, 'start_time' => '10:00'])
        ->assertSessionHasErrors('start_time');

    $this->post('/book', [...$payload, 'start_time' => '10:05'])
        ->assertRedirect(route('booking.index'));

    expect(Booking::where('court_id', $court->id)->count())->toBe(2);
});
