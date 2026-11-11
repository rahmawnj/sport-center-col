<?php

use App\Models\Booking;
use App\Models\BookingTimeSlot;
use App\Models\Court;
use App\Models\Device;
use App\Models\Facility;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function makeBookableFacility(int $turnaround = 0): array
{
    $facility = Facility::create([
        'name' => 'Padel Hall',
        'slug' => 'padel-hall',
        'is_bookable_online' => true,
        'turnaround_minutes' => $turnaround,
        'status' => 'active',
    ]);

    $court = Court::create([
        'facility_id' => $facility->id,
        'name' => 'Lapangan 1',
        'status' => 'available',
    ]);

    $day = strtolower(Carbon::parse('next monday')->englishDayOfWeek);
    BookingTimeSlot::create([
        'facility_id' => $facility->id,
        'day' => $day,
        'start_time' => '09:00',
        'end_time' => '16:00',
        'price' => 250000,
        'is_active' => true,
    ]);
    BookingTimeSlot::create([
        'facility_id' => $facility->id,
        'day' => $day,
        'start_time' => '16:00',
        'end_time' => '23:00',
        'price' => 300000,
        'is_active' => true,
    ]);

    return [$facility, $court];
}

function publicBookingPayload(Facility $facility, Court $court, string $date, array $overrides = []): array
{
    return array_merge([
        'facility_id' => $facility->id,
        'court_id' => $court->id,
        'date' => $date,
        'start_time' => '09:00',
        'duration_hours' => 1,
        'terms_accepted' => true,
        'guest_name' => 'Budi',
        'guest_email' => 'budi@example.com',
        'guest_phone' => '08123456789',
    ], $overrides);
}

it('returns hourly booking slots using the facility master rates without a package', function () {
    [$facility] = makeBookableFacility();
    $date = Carbon::parse('next monday')->toDateString();

    $response = $this->getJson("/book/availability?facility_id={$facility->id}&date={$date}");

    $response->assertOk();
    $slots = collect($response->json('slots'))->keyBy('start_time');

    expect($slots)->not->toBeEmpty();
    expect((float) $slots['09:00']['price'])->toBe(250000.0);
    expect((float) $slots['16:00']['price'])->toBe(300000.0);
    expect($slots['09:00']['end_time'])->toBe('10:00');
});

it('keeps booked courts visible but disables them for the matching date and time', function () {
    [$facility, $court] = makeBookableFacility();
    $date = Carbon::parse('next monday');

    Booking::create([
        'court_id' => $court->id,
        'date' => $date->toDateString(),
        'start_time' => '09:00',
        'end_time' => '10:00',
        'status' => 'pending',
    ]);

    $slots = collect($this->getJson("/book/availability?facility_id={$facility->id}&date={$date->toDateString()}")
        ->assertOk()
        ->json('slots'))
        ->keyBy('start_time');

    expect($slots['09:00']['courts'])->toHaveCount(1);
    expect($slots['09:00']['courts'][0]['id'])->toBe($court->id);
    expect($slots['09:00']['courts'][0]['is_available'])->toBeFalse();
    expect($slots['10:00']['courts'][0]['is_available'])->toBeTrue();
});

it('creates a booking and pending transaction without saving a package', function () {
    [$facility, $court] = makeBookableFacility();
    $date = Carbon::parse('next monday')->toDateString();

    $this->post('/book', publicBookingPayload($facility, $court, $date))
        ->assertRedirect();

    $booking = Booking::sole();
    $transaction = Transaction::sole();

    expect(substr((string) $booking->end_time, 0, 5))->toBe('10:00');
    expect($booking->status)->toBe('pending');
    expect($transaction->transactionable_type)->toBe('booking');
    expect((float) $transaction->total_amount)->toBe(250000.0);
});

it('binds a booking to the selected court device', function () {
    [$facility, $court] = makeBookableFacility();
    $device = Device::create([
        'facility_id' => $facility->id,
        'court_id' => $court->id,
        'name' => 'Padel Court A Controller',
        'type' => 'controller',
        'device_code' => 'PADEL-TEST-01',
        'status' => 'active',
    ]);

    $this->post('/book', publicBookingPayload($facility, $court, Carbon::parse('next monday')->toDateString()))
        ->assertRedirect();

    expect(Booking::sole()->device_id)->toBe($device->id);
});

it('adds the court price adjustment to the total', function () {
    [$facility, $court] = makeBookableFacility();
    $court->update(['price_adjustment' => 25000]);

    $this->post('/book', publicBookingPayload($facility, $court, Carbon::parse('next monday')->toDateString()))
        ->assertRedirect();

    $transaction = Transaction::sole();
    expect((float) $transaction->total_amount)->toBe(275000.0);
});

it('spaces hourly choices by the facility turnaround time', function () {
    [$facility] = makeBookableFacility(5);
    $date = Carbon::parse('next monday')->toDateString();

    $slots = collect($this->getJson("/book/availability?facility_id={$facility->id}&date={$date}")
        ->assertOk()
        ->json('slots'))
        ->keyBy('start_time');

    expect($slots)->toHaveKey('09:00');
    expect($slots)->toHaveKey('10:05');
    expect($slots)->not->toHaveKey('10:00');
});

it('enforces the turnaround gap and accepts the next valid booking start', function () {
    [$facility, $court] = makeBookableFacility(5);
    $date = Carbon::parse('next monday')->toDateString();

    Booking::create([
        'court_id' => $court->id,
        'date' => $date,
        'start_time' => '09:00',
        'end_time' => '10:00',
        'status' => 'confirmed',
    ]);

    $this->post('/book', publicBookingPayload($facility, $court, $date, ['start_time' => '10:00']))
        ->assertSessionHasErrors('start_time');

    $this->post('/book', publicBookingPayload($facility, $court, $date, ['start_time' => '10:05']))
        ->assertRedirect();

    expect(Booking::where('court_id', $court->id)->count())->toBe(2);
});

it('allows selecting multiple consecutive hours from the same master rate window', function () {
    [$facility, $court] = makeBookableFacility(5);
    $date = Carbon::parse('next monday')->toDateString();

    $this->post('/book', publicBookingPayload($facility, $court, $date, [
        'start_time' => '09:00',
        'duration_hours' => 2,
    ]))->assertRedirect();

    $booking = Booking::sole();
    $transaction = Transaction::sole();

    expect(substr((string) $booking->end_time, 0, 5))->toBe('11:00');
    expect((float) $transaction->total_amount)->toBe(500000.0);
});
