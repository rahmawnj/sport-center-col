<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MemberProfile extends Model
{
    protected $fillable = [
        'user_id',
        'member_code',
        'qr_code',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected static function booted(): void
    {
        static::creating(function (MemberProfile $profile) {
            $profile->member_code ??= self::makeMemberCode();
            $profile->qr_code ??= self::makeQrCode();
        });
    }

    public static function makeMemberCode(): string
    {
        $digits = str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);

        return 'MEM'.strtoupper(Str::random(4)).$digits;
    }

    public static function makeQrCode(): string
    {
        $digits = implode('', array_map(fn () => random_int(0, 9), range(1, 16)));

        return strtoupper(Str::random(8)).$digits;
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
