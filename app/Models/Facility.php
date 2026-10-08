<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_bookable_online',
        'turnaround_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_bookable_online' => 'boolean',
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
}
