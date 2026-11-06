<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'guest_name',
        'customer_name',
        'amount',
        'total_amount',
        'invoice_number',
        'booking_code',
        'payment_method',
        'payment_status',
        'booking_status',
        'transactionable_type',
        'handled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            $transaction->invoice_number ??= 'INV'.now()->format('YmdHis').strtoupper(Str::random(4));

            if ($transaction->payment_status === 'paid') {
                $transaction->payment_status = 'fully_paid';
            } elseif ($transaction->payment_status === 'pending') {
                $transaction->payment_status = 'unpaid';
            }

            if ($transaction->payment_method === 'qris') {
                $transaction->payment_method = 'bank_transfer';
            }
        });
    }

    public function setAmountAttribute(mixed $value): void
    {
        $this->attributes['total_amount'] = $value;
    }

    public function getAmountAttribute(): mixed
    {
        return $this->attributes['total_amount'] ?? null;
    }

    public function setCustomerNameAttribute(?string $value): void
    {
        $this->attributes['guest_name'] = $value;
    }

    public function getCustomerNameAttribute(): ?string
    {
        return $this->attributes['guest_name'] ?? null;
    }

    public function setBookingCodeAttribute(?string $value): void
    {
        $this->attributes['invoice_number'] = $value;
    }

    public function getBookingCodeAttribute(): ?string
    {
        return $this->attributes['invoice_number'] ?? null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
