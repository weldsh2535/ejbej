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
            //;
            $total = count($products);
            // $perPage = $request->query('per_page', 10);
            // $page = $request->query('page', 1);
            // $pagedData = new LengthAwarePaginator(
            //     array_slice($products, ($page - 1) * $perPage, $perPage),
            //     $total,
            //     $perPage,
            //     $page,
            //     ['path' => url()->current()]
            // );

            return response()->json([
                'status' => 200,
                'total' => $total,
                'data' => $products,
                // "numberofpages" => $pagedData->lastPage(),
                "errors" => []
            ], 200);
        } catch (\Exception $e) {
            // Log the error message for debugging
            // \Log::error('Error fetching product status: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
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
