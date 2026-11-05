<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(
            Trainer::class,
            'trainer_package_trainer',
            'trainer_package_id',
            'trainer_id'
        )->withTimestamps();
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'trainer_package_facility')->withTimestamps();
    }

    public function memberPackages(): HasMany
    {
        return $this->hasMany(MemberTrainerPackage::class);
    }
}
