<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PricingRate extends Model
{
    use HasFactory;

    protected  = [
        'zone_space_id',
        'trainer_id', // For trainer-based pricing
        'rental_type', // Type seperti Reguler, Promo Happy Hour
        'price',
        'unit_type',
        'min_booking_duration',
        'day_of_week', // CSV format: '1,2,3,4,5' untuk Weekday
        'start_time', // Time-based pricing
        'end_time', // Time-based pricing
        'is_active',
    ];

    protected  = [
        'price' => 'decimal:2',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'is_active' => 'boolean',
    ];

    /**
     * Get the zone space this pricing applies to
     */
    public function zoneSpace(): BelongsTo
    {
        return ->belongsTo(ZoneSpace::class);
    }

    /**
     * Get the trainer this pricing is for (nullable)
     */
    public function trainer(): BelongsTo
    {
        return ->belongsTo(Trainer::class);
    }

    /**
     * Check if pricing is valid for current day
     */
    public function isValidForDay(Carbon  = null): bool
    {
         =  ?: now();
         = ->dayOfWeek; // 1-7 (1=Monday)

        // If day_of_week is null, available every day
        if (is_null(->day_of_week)) {
            return true;
        }

        // Check if current day is in allowed days
         = explode(',', ->day_of_week);
        return in_array(, array_map('intval', ));
    }

    /**
     * Check if pricing is valid for current time
     */
    public function isValidForTime(Carbon  = null): bool
    {
         =  ?: now();

        // If no time restrictions, always valid
        if (is_null(->start_time) && is_null(->end_time)) {
            return true;
        }

        // Check time range
        if (->start_time && ->lt(->start_time)) {
            return false;
        }

        if (->end_time && ->gt(->end_time)) {
            return false;
        }

        return true;
    }

    /**
     * Check if pricing applies now
     */
    public function isApplicableNow(Carbon  = null): bool
    {
        return ->is_active && 
               ->isValidForDay() && 
               ->isValidForTime();
    }

    /**
     * Get available pricing rates for a zone space at specific time
     */
    public static function getApplicableForZoneSpace(int , Carbon  = null): \Illuminate\Database\Eloquent\Collection
    {
         =  ?: now();
        
        return static::where('zone_space_id', )
                    ->where('is_active', true)
                    ->where(function () use (, ) {
                        // Check day of week
                        ->whereNull('day_of_week')
                              ->orWhere('day_of_week', 'LIKE', '%' . ->dayOfWeek . '%');
                        
                        // Check time range
                        ->where(function () use () {
                            ->whereNull('start_time')
                              ->whereNull('end_time')
                              ->orWhere(function () use () {
                                ->where('start_time', '<=', ->format('H:i:s'))
                                      ->where('end_time', '>=', ->format('H:i:s'));
                              });
                        });
                    })
                    ->get();
    }
}
