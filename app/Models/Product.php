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
