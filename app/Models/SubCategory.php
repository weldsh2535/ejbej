<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCategory extends Model
{
    use HasFactory;

    protected $tables ='sub_categories';

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'image', 'icon',
        'meta_title', 'meta_description', 'is_active', 'position'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
