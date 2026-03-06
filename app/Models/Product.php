<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title',
        'location',
        'category_id',
        'description',
        'price',
        'brand',
        'is_active',
        'user_id'
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
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    // Check if product is favorited by current user
    public function getIsFavoritedAttribute()
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->favorites()
            ->where('user_id', auth()->id())
            ->exists();
    }

    // Get favorites count
    public function getFavoritesCountAttribute()
    {
        return $this->favorites()->count();
    }

    /**
     * Scope active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope by price range
     */
    public function scopePriceBetween($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Scope by search term
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
                ->orWhere('description', 'LIKE', "%{$term}%")
                ->orWhere('brand', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Scope by location
     */
    public function scopeInLocation($query, $location)
    {
        return $query->where('location', 'LIKE', "%{$location}%");
    }
}
