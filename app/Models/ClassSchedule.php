<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    protected $fillable = [
        'class_id',
        'trainer_id',
        'session_date',
        'start_time',
        'end_time',
        'capacity',
        'price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'capacity' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function sportClass(): BelongsTo
    {
        return $this->belongsTo(SportClass::class, 'class_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class)->withTrashed();
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class, 'class_schedule_id');
    }
}
