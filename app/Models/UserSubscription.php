<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'membership_package_id',
        'start_date',
        'end_date',
        'used_sessions', // Jumlah sesi yang sudah dipakai
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'used_sessions' => 'integer',
    ];

    /**
     * Get the user that owns the subscription
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the membership package for this subscription
     *
     * @return BelongsTo<MembershipPackage, $this>
     */
    public function membershipPackage(): BelongsTo
    {
        return $this->belongsTo(MembershipPackage::class);
    }

    /**
     * Check if subscription is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->end_date >= now()->toDateString();
    }

    /**
     * Get remaining sessions 
     */
    public function getRemainingSessions(): int
    {
        return $this->membershipPackage?->getRemainingSessions($this) ?? 0;
    }

    /**
     * Increment used sessions
     */
    public function incrementUsedSessions(): void
    {
        $this->increment('used_sessions');
    }
}
