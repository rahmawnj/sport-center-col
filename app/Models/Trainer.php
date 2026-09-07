<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainer extends Model
{
    use HasFactory, SoftDeletes;

    protected  = [
        'name',
        'specialty',
        // Phone field dihapus sesuai dengan SQL original
    ];

    // Tidak ada phone field sesuai dengan SQL

    /**
     * Get pricing rates for this trainer
     */
    public function pricingRates(): HasMany
    {
        return ->hasMany(PricingRate::class);
    }

    /**
     * Get transaction details where this trainer was assigned
     */
    public function transactionDetails(): HasMany
    {
        return ->hasMany(TransactionDetail::class);
    }

    /**
     * Scopes for available trainers
     */
    public function scopeActive()
    {
        return ->whereNull('deleted_at');
    }
}
