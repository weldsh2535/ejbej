<?php
// app/Models/ProductImage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'product_images';

    protected $fillable = [
        'product_id', 'path', 'filename', 'mime_type',
        'size', 'sort_order', 'is_primary', 'alt_text'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'size' => 'integer',
        'sort_order' => 'integer'
    ];

    protected $appends = ['url', 'formatted_size', 'thumbnail_urls'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the URL for the image
     */
    public function getUrlAttribute(): string
    {
        if (!$this->filename) {
            return asset('images/default-product.png');
        }
        
        $path = 'uploads/products/' . $this->product_id . '/' . $this->filename;
        
        if (file_exists(public_path($path))) {
            return asset($path);
        }
        
        return asset('images/default-product.png');
    }

    /**
     * Get all thumbnail URLs as an array
     */
    public function getThumbnailUrlsAttribute(): array
    {
        return [
            'small' => $this->getThumbnailUrl('small'),
            'medium' => $this->getThumbnailUrl('medium'),
            'large' => $this->getThumbnailUrl('large'),
        ];
    }

    /**
     * Get specific thumbnail URL
     */
    public function getThumbnailUrl(string $size): ?string
    {
        if (!$this->filename) {
            return null;
        }

        $thumbnailPath = 'uploads/products/' . $this->product_id . '/thumbnails/' . $size . '/' . $this->filename;
        
        if (file_exists(public_path($thumbnailPath))) {
            return asset($thumbnailPath);
        }
        
        return null;
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) {
            $path = public_path('uploads/products/' . $this->product_id . '/' . $this->filename);
            
            if (file_exists($path)) {
                $bytes = filesize($path);
                $units = ['B', 'KB', 'MB', 'GB'];
                
                for ($i = 0; $bytes > 1024; $i++) {
                    $bytes /= 1024;
                }
                
                return round($bytes, 2) . ' ' . $units[$i];
            }
            
            return 'N/A';
        }

        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if thumbnails exist
     */
    public function hasThumbnails(): bool
    {
        return $this->getThumbnailUrl('small') !== null;
    }
}