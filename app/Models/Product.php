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
    /**
     * Get the user that owns the product
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the category that the product belongs to
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    protected $appends = ['image_url'];

    // Define the accessor
    public function getImageUrlAttribute()
    {
        // If your image is stored in storage/app/public/products
        if ($this->image) {
            return asset('uploads/products/' . $this->image);
        }

        // Or if stored in public/uploads/products


        // Return default image if no image
        return asset('images/default-product.png');
    }
}
