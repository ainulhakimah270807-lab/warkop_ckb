<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $attributes = ['stock' => 0];

    protected $fillable = ['title', 'category', 'content', 'price', 'stock_type', 'is_active', 'stock', 'photo_path'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'stock' => 'integer',
    ];

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'product_recipes')->withPivot('quantity', 'unit');
    }
}
