<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class UserMembership extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'package_id',
        'device_id',
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

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date !== null
            && $this->start_date->toDateString() <= today()->toDateString()
            && $this->end_date !== null
            && $this->end_date->toDateString() >= today()->toDateString();
    }
}
