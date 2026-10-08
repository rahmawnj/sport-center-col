<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'amount',
        'payment_method',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            $transaction->invoice_number ??= 'INV'.now()->format('YmdHis').strtoupper(Str::random(4));
            $transaction->qr_code ??= self::makeQrCode();
        });
    }

    public static function makeQrCode(): string
    {
        $digits = implode('', array_map(fn () => random_int(0, 9), range(1, 16)));

        return strtoupper(Str::random(8)).$digits;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
