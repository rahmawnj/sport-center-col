<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $fillable = [
        'name',
        'pricing_model',
        'is_online_bookable',
    ];

    protected function casts(): array
    {
        return [
            'is_online_bookable' => 'boolean',
        ];
    }

    public function zoneSpaces(): HasMany
    {
        return $this->hasMany(ZoneSpace::class);
    }
}
