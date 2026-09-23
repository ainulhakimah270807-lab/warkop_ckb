<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'unit', 'stock', 'max_stock', 'minimum_stock'];
    protected $casts = ['stock' => 'float', 'max_stock' => 'float', 'minimum_stock' => 'float'];
}
