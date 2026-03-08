<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::orderBy('created_at', 'desc')
                ->get(['id', 'name', 'slug', 'image', 'is_active']); // Added 'image' here

            // Transform the data to include full image URL
            $categories->transform(function ($category) {
                if ($category->image) {
                    $category->image_url = url('uploads/categories/' . $category->image);
                } else {
                    $category->image_url = null;
                }
                return $category;
            });

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


    public function getByCategory(Request $request, $categoryId)
    {
        try {
            // Validate category exists
            $category = Category::find($categoryId);

            if (!$category) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Category not found',
                    'errors' => ['Category with ID ' . $categoryId . ' does not exist']
                ], 404);
            }

            // Build query with filters
            $query = SubCategory::where('category_id', $categoryId);

            // Search by name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sort options
            $sortField = $request->query('sort_by', 'name');
            $sortOrder = $request->query('sort_order', 'asc');
            $allowedSortFields = ['name', 'created_at', 'id'];

            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortOrder);
            }

            // Pagination
            $perPage = $request->query('per_page', 15);
            $subcategories = $query->paginate($perPage);

            // Transform data
            $subcategories->getCollection()->transform(function ($subcategory) {
                $subcategory->image_url = $subcategory->image
                    ? url('uploads/subcategories/' . $subcategory->image)
                    : null;
                return $subcategory;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Subcategories retrieved successfully',
                'filters' => [
                    'category_id' => (int) $categoryId,
                    'category_name' => $category->name,
                    'search' => $request->search,
                    'is_active' => $request->is_active
                ],
                'pagination' => [
                    'total' => $subcategories->total(),
                    'per_page' => $subcategories->perPage(),
                    'current_page' => $subcategories->currentPage(),
                    'last_page' => $subcategories->lastPage()
                ],
                'data' => $subcategories->items(),
                'errors' => []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // Find the category by ID
            $category = Category::select(['id', 'name', 'slug', 'description', 'image', 'is_active', 'created_at', 'updated_at'])
                ->find($id);

            // Check if category exists
            if (!$category) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Category not found',
                    'errors' => ['Category with ID ' . $id . ' does not exist']
                ], 404);
            }

            // Transform the data to include full image URL
            $categoryData = $category->toArray();
            $categoryData['image_url'] = $category->image
                ? url('uploads/categories/' . $category->image)
                : null;

            return response()->json([
                'status' => 200,
                'message' => 'Category retrieved successfully',
                'data' => $categoryData,
                'errors' => []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destory($id)
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
