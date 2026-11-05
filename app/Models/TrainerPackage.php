<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerPackage extends Model
{
    protected $fillable = [
        'name',
        'facility_id',
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

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(
            Trainer::class,
            'trainer_package_trainer',
            'trainer_package_id',
            'trainer_id'
        )->withTimestamps();
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function memberPackages(): HasMany
    {
        return $this->hasMany(MemberTrainerPackage::class);
    }
}
