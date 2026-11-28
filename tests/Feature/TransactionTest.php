<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\Facility;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function makeFacilityWithPackage(string $type): array
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
        'name' => $type === 'session' ? 'Padel 1 Jam' : 'Tiket Harian',
        'type' => $type,
        'duration_minutes' => 60,
    ]);

    return [$facility, $court, $package];
}

it('records a direct over-the-counter sale linked to the package', function () {
    $user = User::factory()->create();
    [, , $package] = makeFacilityWithPackage('entry');

    $this->actingAs($user)->post('/transactions', [
        'package_id' => $package->id,
        'pricing_rule_id' => null,
        'quantity' => 2,
        'customer_name' => 'Walk-in',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'amount' => 500000,
    ])->assertRedirect(route('transactions.index'));

    $transaction = Transaction::sole();

    expect((float) $transaction->amount)->toBe(500000.0);
    expect($transaction->transactionable_type)->toBe(Package::class);
    expect($transaction->customer_name)->toBe('Walk-in');

    $this->actingAs($user)
        ->get("/transactions/{$transaction->id}")
        ->assertOk();
});

it('creates a confirmed booking for an over-the-counter session sale', function () {
    $user = User::factory()->create();
    [, $court, $package] = makeFacilityWithPackage('session');
    $date = Carbon::parse('next monday')->toDateString();

    $this->actingAs($user)->post('/transactions', [
        'package_id' => $package->id,
        'pricing_rule_id' => null,
        'quantity' => 1,
        'customer_name' => 'Budi',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'amount' => 250000,
        'court_id' => $court->id,
        'date' => $date,
        'start_time' => '09:00',
    ])->assertRedirect(route('transactions.index'));

    $booking = Booking::sole();

    expect(substr($booking->start_time, 0, 5))->toBe('09:00');
    expect(substr($booking->end_time, 0, 5))->toBe('10:00');
    expect($booking->status)->toBe('confirmed');
    expect((float) $booking->transactions()->sole()->amount)->toBe(250000.0);
});

it('rejects a session sale on an already booked court slot', function () {
    $user = User::factory()->create();
    [, $court, $package] = makeFacilityWithPackage('session');
    $date = Carbon::parse('next monday')->toDateString();

    Booking::create([
        'court_id' => $court->id,
        'date' => $date,
        'start_time' => '09:00',
        'end_time' => '10:00',
        'status' => 'confirmed',
    ]);

    $this->actingAs($user)
        ->post('/transactions', [
            'package_id' => $package->id,
            'quantity' => 1,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'amount' => 250000,
            'court_id' => $court->id,
            'date' => $date,
            'start_time' => '09:00',
        ])
        ->assertSessionHasErrors('start_time');

    expect(Booking::count())->toBe(1);
});

it('rejects a same-day session sale for a time that already passed', function () {
    $user = User::factory()->create();
    [, $court, $package] = makeFacilityWithPackage('session');

    $now = Carbon::now();

    if ($now->hour === 0 && $now->minute === 0) {
        $this->markTestSkipped('No earlier time available today.');
    }

    $this->actingAs($user)->post('/transactions', [
        'package_id' => $package->id,
        'quantity' => 1,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'amount' => 250000,
        'court_id' => $court->id,
        'date' => $now->toDateString(),
        'start_time' => '00:00',
    ])->assertSessionHasErrors('start_time');

    expect(Booking::count())->toBe(0);
});

it('generates an invoice number and qr code for a transaction', function () {
    $user = User::factory()->create();
    [, , $package] = makeFacilityWithPackage('entry');

    $this->actingAs($user)->post('/transactions', [
        'package_id' => $package->id,
        'quantity' => 1,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'amount' => 50000,
    ])->assertRedirect();

    $transaction = Transaction::latest('id')->sole();

    expect($transaction->invoice_number)->toMatch('/^INV\d{14}[A-Z0-9]{4}$/');
    expect($transaction->qr_code)->toMatch('/^[A-Z0-9]{8}\d{16}$/');

    $this->get('/verify?code='.$transaction->qr_code)->assertOk();
});

it('creates a flexible booking from the cashier with prorated tariff', function () {
    $user = User::factory()->create();
    [$facility, $court] = makeFacilityWithPackage('session');
    $facility->update(['has_booking' => true, 'has_flexible_booking' => true]);
    $day = strtolower(Carbon::parse('next monday')->englishDayOfWeek);

    \App\Models\BookingTimeSlot::create([
        'facility_id' => $facility->id,
        'day' => $day,
        'start_time' => '09:00',
        'end_time' => '16:00',
        'price' => 10000,
        'is_active' => true,
    ]);
    \App\Models\BookingTimeSlot::create([
        'facility_id' => $facility->id,
        'day' => $day,
        'start_time' => '16:00',
        'end_time' => '23:00',
        'price' => 20000,
        'is_active' => true,
    ]);

    $this->actingAs($user)->post('/transactions', [
        'facility_id' => $facility->id,
        'booking_type' => 'flexible',
        'duration_minutes' => 90,
        'quantity' => 1,
        'customer_name' => 'Budi',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'amount' => 35000,
        'court_id' => $court->id,
        'date' => Carbon::parse('next monday')->toDateString(),
        'start_time' => '15:30',
        'end_time' => '17:00',
        'selected_slots' => [],
    ])->assertRedirect(route('transactions.index'));

    $booking = Booking::sole();
    $transaction = Transaction::sole();

    expect($booking->booking_type)->toBe('flexible');
    expect(substr((string) $booking->end_time, 0, 5))->toBe('17:00');
    expect((float) $transaction->amount)->toBe(35000.0);
    expect($booking->price_breakdown)->toHaveCount(2);
});

it('rejects a flexible cashier booking when the selected court is occupied', function () {
    $user = User::factory()->create();
    [$facility, $court] = makeFacilityWithPackage('session');
    $facility->update(['has_booking' => true, 'has_flexible_booking' => true]);
    $day = strtolower(Carbon::parse('next monday')->englishDayOfWeek);
    \App\Models\BookingTimeSlot::create([
        'facility_id' => $facility->id,
        'day' => $day,
        'start_time' => '09:00',
        'end_time' => '23:00',
        'price' => 10000,
        'is_active' => true,
    ]);
    Booking::create([
        'court_id' => $court->id,
        'date' => Carbon::parse('next monday')->toDateString(),
        'start_time' => '15:00',
        'end_time' => '16:00',
        'status' => 'confirmed',
    ]);

    $this->actingAs($user)->post('/transactions', [
        'facility_id' => $facility->id,
        'booking_type' => 'flexible',
        'duration_minutes' => 60,
        'quantity' => 1,
        'customer_name' => 'Budi',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'amount' => 10000,
        'court_id' => $court->id,
        'date' => Carbon::parse('next monday')->toDateString(),
        'start_time' => '15:30',
        'end_time' => '16:30',
        'selected_slots' => [],
    ])->assertSessionHasErrors('start_time');

    expect(Booking::count())->toBe(1);
});
