<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    protected $fillable = [
        'facility_id',
        'name',
        'amenities',
        'price_adjustment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'price_adjustment' => 'decimal:2',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
