<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property int $role_id
 * @property string|null $phone
 * @property string|null $two_factor_secret
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $two_factor_recovery_codes
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'email', 'password', 'role_id', 'phone'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'phone' => 'string',
        ];
    }

    // Role relationship
    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // User subscriptions relationship
    /**
     * @return HasMany<UserSubscription, $this>
     */
    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    // Transactions where user is customer
    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    // Transactions where user handled the transaction
    /**
     * @return HasMany<Transaction, $this>
     */
    public function handledTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'handled_by');
    }

    // Cash transactions
    /**
     * @return HasMany<CashTransaction, $this>
     */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'handled_by');
    }

    // Daily closings
    /**
     * @return HasMany<DailyClosing, $this>
     */
    public function dailyClosings(): HasMany
    {
        return $this->hasMany(DailyClosing::class, 'closed_by');
    }

    // Gate access logs
    /**
     * @return HasMany<GateAccessLog, $this>
     */
    public function gateAccessLogs(): HasMany
    {
        return $this->hasMany(GateAccessLog::class);
    }

    // IoT logs
    /**
     * @return HasMany<IotLog, $this>
     */
    public function iotLogs(): HasMany
    {
        return $this->hasMany(IotLog::class, 'triggered_by');
    }

    // Accessory methods
    public function isAdmin(): bool
    {
        return in_array($this->role->name ?? '', ['Superadmin', 'Admin']);
    }

    public function isMember(): bool
    {
        return $this->role?->name === 'Member';
    }

    public function hasActiveSubscription(): bool
    {
        return $this->userSubscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->exists();
    }

    public function getActiveSubscription(): ?UserSubscription
    {
        return $this->userSubscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->first();
    }
}
