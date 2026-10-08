<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UserMembership extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function isActive(): bool
    {
        return $this->status !== 'cancelled'
            && $this->end_date !== null
            && $this->end_date->toDateString() >= now()->toDateString();
    }
}
