<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trainer extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'specialty', 'phone'];

    public function facilities(): BelongsToMany\n    {\n        return $this->belongsToMany(Facility::class)->withTimestamps();\n    }\n\n    public function memberPackages(): HasMany
    {
        return $this->hasMany(MemberTrainerPackage::class);
    }
}
