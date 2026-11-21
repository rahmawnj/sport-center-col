<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_bookable_online',
        'has_membership',
        'has_booking',
        'has_session',
        'turnaround_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_bookable_online' => 'boolean',
            'has_membership' => 'boolean',
            'has_booking' => 'boolean',
            'has_session' => 'boolean',
            'turnaround_minutes' => 'integer',
        ];
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SportClass::class);
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class)->withTimestamps();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
