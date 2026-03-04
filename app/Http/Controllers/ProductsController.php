<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductsController extends Controller
{
    //
    public function get()
    {
        try {
            $products = Product::orderBy('created_at', 'desc')->get();

            // Transform the collection to add image URLs
            $products->transform(function ($product) {
                $product->image_url = $this->getProductImageUrl($product);
                // Optionally hide the raw image field if you don't want to expose it
                // unset($product->image);
                return $product;
            });

            $total = count($products);

            return response()->json([
                'status' => 200,
                'total' => $total,
                'data' => $products,
                "errors" => []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getProductImageUrl($product)
    {
        if (!$product->image) {
            return asset('images/default-product.png');
        }

        // Check if the image is already a full URL
        if (filter_var($product->image, FILTER_VALIDATE_URL)) {
            return $product->image;
        }

        // Adjust this path based on where your images are stored
        // return asset('storage/products/' . $product->image);
        return asset('uploads/products/' . $product->image);
        // or return url('images/products/' . $product->image);
    }
    public function store(Request $data)
    {
        try {
            $imagePath = null;
            if ($data->hasFile('image')) {
                $file = $data->file('image');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $fileName = time() . '_' . Str::slug($originalName) . '.' . $file->getClientOriginalExtension();

                $file->move(public_path('uploads/products/'), $fileName);

                $imagePath = $fileName;
            }
            $post = Product::create([
                'title' => $data['title'],
                'location' => $data['location'],
                'category_id' => $data['category_id'],
                'description' => $data['description'],
                'price' => $data['price'],
                'brand' => $data['brand'],
                'image' => $imagePath,
                'is_active' => $data['is_active'],
                'user_id' => $data['user_id']
            ]);
            return response()->json([
                'status' => 200,
                'data' => $post,
            ], 200);
        } catch (\Exception $e) {
            // Log the error message for debugging
            // \Log::error('Error fetching student status: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
