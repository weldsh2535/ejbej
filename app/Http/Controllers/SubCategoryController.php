<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
    public function get()
    {
        try {
            $categorys = SubCategory::orderBy('created_at', 'desc')->get();
            //;
            $total = count($categorys);
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
                'data' => $categorys,
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

                $file->move(public_path('uploads/categorys/'), $fileName);

                $imagePath = $fileName;
            }
            $post = SubCategory::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'image' => $imagePath,
                'is_active' => $data['is_active']
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
