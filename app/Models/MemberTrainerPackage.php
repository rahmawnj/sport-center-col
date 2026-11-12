<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberTrainerPackage extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'trainer_package_id',
        'trainer_id',
        'facility_id',
        'start_date',
        'expires_at',
        'total_sessions',
        'used_sessions',
        'trainer_share_percentage',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expires_at' => 'date',
            'total_sessions' => 'integer',
            'used_sessions' => 'integer',
            'trainer_share_percentage' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function trainerPackage(): BelongsTo
    {
        return $this->belongsTo(TrainerPackage::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function getRemainingSessionsAttribute(): int
    {
        return max(0, $this->total_sessions - $this->used_sessions);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date !== null
            && $this->start_date->lte(today())
            && $this->expires_at !== null
            && $this->expires_at->gte(today())
            && $this->used_sessions < $this->total_sessions;
    }

    public function refreshStatus(): void
    {
        if (in_array($this->status, ['cancelled', 'pending'], true)) {
            return;
        }

        if ($this->start_date->gt(today())) {
            return;
        }

        if ($this->expires_at->lt(today())) {
            $this->status = 'expired';
        } elseif ($this->used_sessions >= $this->total_sessions) {
            $this->status = 'completed';
        } else {
            $this->status = 'active';
        }

        $this->save();
    }
}
