<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionPaymentProof extends Model
{
    protected $table = 'transaction_payment_proofs';

    protected $fillable = [
        'transaction_id',
        'payment_option',
        'amount_paid',
        'proof_path',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
