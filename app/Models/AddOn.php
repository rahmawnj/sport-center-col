<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddOn extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'price', 'stock'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'stock' => 'integer'];
    }
}
