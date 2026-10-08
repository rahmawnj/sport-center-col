<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Package extends Model
{
    protected $fillable = [
        'facility_id',
        'name',
        'type',
        'duration_minutes',
        'duration_days',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'duration_days' => 'integer',
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

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }
}
