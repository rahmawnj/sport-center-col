<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trainer extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'specialty', 'phone'];

    public function memberPackages(): HasMany
    {
        return $this->hasMany(MemberTrainerPackage::class);
    }
}
