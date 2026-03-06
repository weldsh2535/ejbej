<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'location', 'category_id', 'description',
        'price', 'brand', 'is_active', 'user_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->primaryImage?->url;
    }

    public function getImageUrlsAttribute(): array
    {
        return $this->images->map->url->toArray();
    }
}