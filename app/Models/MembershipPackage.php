<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipPackage extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'price', 'duration_days', 'description'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'duration_days' => 'integer'];
    }
}
