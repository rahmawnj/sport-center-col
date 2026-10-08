<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagePricingRule extends Model
{
    protected $fillable = [
        'package_id',
        'day_type',
        'date_start',
        'date_end',
        'start_time',
        'end_time',
        'price',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'date_start' => 'date',
            'date_end' => 'date',
            'price' => 'decimal:2',
            'priority' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
