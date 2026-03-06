<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function get()
    {
        try {
            $categories = Category::orderBy('created_at', 'desc')
                ->get(['id', 'name', 'slug', 'is_active']);

            return response()->json([
                'status' => 200,
                'total' => $categories->count(),
                'data' => $categories,
                "errors" => []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!',
                'error' => config('app.debug') ? $e->getMessage() : null,
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

                $file->move(public_path('uploads/categories/'), $fileName);

                $imagePath = $fileName;
            }
            $post = Category::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
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

    public function update(Request $request, $id)
    {
        try {
            $categorys = Category::find($id);
            $categorys->update([
                'name' => $request->name ?? $categorys->name,
                'slug' => $request->slug ?? $categorys->slug,
                'description' => $request->description ?? $categorys->description
            ]);
            return response()->json([
                'status' => true,
                'data' => [],
                "summary" => "Category updated successfully",
                "errors" => []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => [],
                'summary' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            // Find the document by ID
            $categorys = Category::find($id);

            // Check if the document exists
            if (!$categorys) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category file not found.'
                ], 404); // Not Found
            }

            // Define the path to the old file
            $old_file = public_path('uploads/categories/' . $categorys->name);

            // Check if the file exists before attempting to delete it
            if (file_exists($old_file)) {
                unlink($old_file);
            }

            // Attempt to delete the categorys
            if ($categorys->delete()) {
                return response()->json([
                    'status' => true,
                    'data' => [],
                    "summary" => "Category deleted successfully",
                    "errors" => []
                ], 200); // OK
            } else {
                return response()->json([
                    'status' => true,
                    'data' => [],
                    'summary' => 'Something went wrong while deleting the categorys.',
                    "errors" => []
                ], 500); // Internal Server Error
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => [],
                'summary' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
