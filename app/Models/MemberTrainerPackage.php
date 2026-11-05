<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberTrainerPackage extends Model
{
    protected $fillable = [
        'user_id',
        'trainer_package_id',
        'trainer_id',\n        'facility_id',
        'start_date',
        'expires_at',
        'total_sessions',
        'used_sessions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expires_at' => 'date',
            'total_sessions' => 'integer',
            'used_sessions' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainerPackage(): BelongsTo
    {
        return $this->belongsTo(TrainerPackage::class);
    }

    public function facility(): BelongsTo\n    {\n        return $this->belongsTo(Facility::class);\n    }\n\n    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function getRemainingSessionsAttribute(): int
    {
        return max(0, $this->total_sessions - $this->used_sessions);
    }

    public function refreshStatus(): void
    {
        if (in_array($this->status, ['cancelled', 'pending'], true)) {
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
