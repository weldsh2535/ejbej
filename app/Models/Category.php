<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'image', 'icon',
        'meta_title', 'meta_description', 'is_featured', 
        'is_active', 'position'
    ];

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class)->orderBy('position');
    }

    public function activeSubcategories(): HasMany
    {
        return $this->subcategories()->where('is_active', true);
    }
}
