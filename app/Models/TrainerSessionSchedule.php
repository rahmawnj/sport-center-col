<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerSessionSchedule extends Model
{
    protected $fillable = [
        'member_trainer_package_id', 'session_date', 'start_time', 'end_time',
        'status', 'member_attended', 'trainer_attended',
        'member_confirmed_at', 'trainer_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'member_attended' => 'boolean',
            'trainer_attended' => 'boolean',
            'member_confirmed_at' => 'datetime',
            'trainer_confirmed_at' => 'datetime',
        ];
    }

    public function memberTrainerPackage(): BelongsTo
    {
        return $this->belongsTo(MemberTrainerPackage::class);
    }
}
