<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductsController extends Controller
{
    //
    public function get(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);

            // Log the query to see what's happening
            \Log::info('Fetching products with user relationship');

            $products = Product::with('user') // Eager load user
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Log the count of products
            \Log::info('Products found: ' . $products->count());

            return response()->json([
                'status' => 200,
                'message' => 'Products retrieved successfully',
                ...(new ProductCollection($products))->toArray($request),
                "errors" => []
            ], 200);
        } catch (\Exception $e) {
            // Log the full error with stack trace
            \Log::error('Product fetch error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve products: ' . $e->getMessage(), // Temporarily show error for debugging
                'errors' => ['server' => [$e->getMessage()]]
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'category_id' => 'required|integer|exists:categories,id',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'brand' => 'required|string|max:255',
                'is_active' => 'boolean',
                'user_id' => 'required|integer|exists:users,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB max
            ], [
                'image.max' => 'The image must not be larger than 2MB.',
                'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, webp.',
            ]);

            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $this->uploadImage($request->file('image'));
            }

            $product = Product::create([
                'title' => $validatedData['title'],
                'location' => $validatedData['location'],
                'category_id' => $validatedData['category_id'],
                'description' => $validatedData['description'],
                'price' => $validatedData['price'],
                'brand' => $validatedData['brand'],
                'image' => $imagePath,
                'is_active' => $validatedData['is_active'] ?? true,
                'user_id' => $validatedData['user_id']
            ]);

            return response()->json([
                'status' => 201, // 201 Created is more appropriate for store operations
                'data' => $product,
                'message' => 'Product created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log the error with context
            \Log::error('Product creation failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while creating the product. Please try again.'
            ], 500);
        }
    }

    /**
     * Upload image with optimized handling
     */
    private function uploadImage($file)
    {
        try {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $sluggedName = Str::slug($originalName);
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '_' . $sluggedName . '.' . $extension;

            // Create directory if it doesn't exist
            $uploadPath = public_path('uploads/products/');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Optimize image if it's too large (optional, requires Intervention Image package)
            if (in_array($extension, ['jpeg', 'jpg', 'png']) && $file->getSize() > 1024 * 1024) { // > 1MB
                $this->optimizeImage($file, $uploadPath . $fileName);
            } else {
                $file->move($uploadPath, $fileName);
            }

            return $fileName;
        } catch (\Exception $e) {
            \Log::error('Image upload failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Optimize image (requires Intervention Image package)
     * Install: composer require intervention/image
     */
    private function optimizeImage($file, $path)
    {
        if (class_exists('\Intervention\Image\Facades\Image')) {
            \Intervention\Image\Facades\Image::make($file)
                ->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->save($path, 85); // 85% quality
        } else {
            $file->move(dirname($path), basename($path));
        }
    }
}
