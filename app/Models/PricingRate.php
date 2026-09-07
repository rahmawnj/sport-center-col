<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRate extends Model
{
    protected $fillable = [
        'zone_space_id',
        'rental_type',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function zoneSpace(): BelongsTo
    {
        return $this->belongsTo(ZoneSpace::class);
    }
}
