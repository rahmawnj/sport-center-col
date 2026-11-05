<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainerPackage extends Model
{
    protected $fillable = [
        'name',
        'total_sessions',
        'duration_days',
        'price',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_sessions' => 'integer',
            'duration_days' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function memberPackages(): HasMany
    {
        return $this->hasMany(MemberTrainerPackage::class);
    }
}
