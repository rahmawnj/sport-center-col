<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'booking_code',
        'customer_type',
        'user_id',
        'guest_name',
        'payment_method',
        'total_amount',
        'payment_status',
        'booking_status',
        'handled_by',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(TransactionPaymentProof::class);
    }
}
