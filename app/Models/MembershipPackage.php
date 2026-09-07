<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'session_quota', // Batas kedatangan/sesi. NULL jika Unlimited
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'session_quota' => 'integer',
    ];

    /** Paket keanggotaan gym dengan batas kunjungan dan durasi */

    /**
     * Get all user subscriptions for this package
     *
     * @return HasMany<UserSubscription, $this>
     */
    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Check if this package has unlimited sessions
     */
    public function hasUnlimitedSessions(): bool
    {
        return is_null($this->session_quota);
    }

    /**
     * Get remaining sessions for a specific subscription
     */
    public function getRemainingSessions(UserSubscription $subscription): int
    {
        if ($this->hasUnlimitedSessions()) {
            return PHP_INT_MAX;
        }

        return max(0, $this->session_quota - $subscription->used_sessions);
    }
}
