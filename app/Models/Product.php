<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'brand',
        'location',
        'description',
        'price',
        'user_id',
        'image',
        'category_id',
        'is_active'
    ];
}
