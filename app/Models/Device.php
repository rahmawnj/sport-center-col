<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'facility_id',
        'court_id',
        'name',
        'type',
        'device_code',
        'mqtt_topic',
        'status',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Device attached to a specific room.
     */
    public static function forCourt(?int $courtId): ?self
    {
        return $courtId
            ? static::query()->where('court_id', $courtId)->first()
            : null;
    }

    /**
     * Single device that serves the whole sport (no room attached).
     */
    public static function forFacility(?int $facilityId): ?self
    {
        return $facilityId
            ? static::query()->where('facility_id', $facilityId)->whereNull('court_id')->first()
            : null;
    }
}
