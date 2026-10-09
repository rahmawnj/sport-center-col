<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'court_id',
        'zone_space_id',
        'pricing_rate_id',
        'date',
        'start_time',
        'end_time',
        'status',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function zoneSpace(): BelongsTo
    {
        return $this->belongsTo(ZoneSpace::class);
    }

    public function pricingRate(): BelongsTo
    {
        return $this->belongsTo(PricingRate::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }
}
