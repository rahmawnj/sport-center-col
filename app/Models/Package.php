<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'facility_id',
        'name',
        'type',
        'duration_minutes',
        'duration_days',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'duration_days' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PackagePricingRule::class);
    }

    public function userMemberships(): HasMany
    {
        return $this->hasMany(UserMembership::class);
    }
}
