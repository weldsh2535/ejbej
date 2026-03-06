<?php
// app/Console/Commands/FixImagePaths.php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class FixImagePaths extends Command
{
    protected $signature = 'images:fix-paths';
    protected $description = 'Fix image paths and move files to correct location';

    public function handle()
    {
        $this->info('Fixing image paths...');

        // Create storage directory if it doesn't exist
        if (!File::exists(public_path('storage'))) {
            File::makeDirectory(public_path('storage'), 0755, true);
        }

        // Get all product images
        $images = ProductImage::all();

        foreach ($images as $image) {
            $oldPath = public_path('products/' . $image->filename);
            $newPath = storage_path('app/public/products/' . $image->product_id . '/' . $image->filename);

            // Check if file exists in old location
            if (File::exists($oldPath)) {
                // Create directory if it doesn't exist
                File::makeDirectory(dirname($newPath), 0755, true);

                // Move file to new location
                File::move($oldPath, $newPath);

                // Update database path
                $image->path = 'products/' . $image->product_id . '/' . $image->filename;
                $image->save();

                $this->info("Moved: {$image->filename}");
            }
        }

        // Create storage link if it doesn't exist
        if (!File::exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        $this->info('Done!');
    }
}