<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class SubCategoryController extends Controller
{


    public function index(Request $request)
    {
        try {
            // Get pagination parameters from request
            $perPage = $request->query('per_page', 10); // Default 10 items per page
            $page = $request->query('page', 1); // Default page 1

            // Get paginated results from database (more efficient than getting all and then slicing)
            $paginatedCategories = SubCategory::orderBy('created_at', 'desc')
                ->select(['id', 'name', 'slug', 'image', 'is_active'])
                ->paginate($perPage, ['*'], 'page', $page);

            // Transform the data to include full image URL
            $paginatedCategories->getCollection()->transform(function ($category) {
                if ($category->image) {
                    $category->image_url = url('uploads/subcategories/' . $category->image);
                } else {
                    $category->image_url = null;
                }
                return $category;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Subcategories retrieved successfully',
                'data' => $paginatedCategories->items(),
                'pagination' => [
                    'total' => $paginatedCategories->total(),
                    'per_page' => $paginatedCategories->perPage(),
                    'current_page' => $paginatedCategories->currentPage(),
                    'last_page' => $paginatedCategories->lastPage(),
                    'from' => $paginatedCategories->firstItem(),
                    'to' => $paginatedCategories->lastItem(),
                    'next_page_url' => $paginatedCategories->nextPageUrl(),
                    'prev_page_url' => $paginatedCategories->previousPageUrl(),
                ],
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
    public function store(Request $request)
    {
        try {
            $imagePath = null;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $fileName = time() . '_' . Str::slug($originalName) . '.' . $file->getClientOriginalExtension();

                // Create directory if it doesn't exist
                $uploadPath = public_path('uploads/subcategories');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                $file->move($uploadPath, $fileName);
                $imagePath = $fileName;
            }

            $subcategory = SubCategory::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'image' => $imagePath,
                'is_active' => $request->is_active ?? true
            ]);

            // Load relationship and add image URL
            $subcategory->load('category');
            $subcategoryData = $subcategory->toArray();
            $subcategoryData['image_url'] = $imagePath
                ? url('uploads/subcategories/' . $imagePath)
                : null;

            return response()->json([
                'status' => 201,
                'message' => 'Subcategory created successfully',
                'data' => $subcategoryData,
                'errors' => []
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            // Find the subcategory
            $subcategory = SubCategory::find($id);

            if (!$subcategory) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Subcategory not found',
                    'errors' => ['Subcategory does not exist']
                ], 404);
            }

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:sub_categories,name,' . $id,
                'slug' => 'sometimes|string|max:255|unique:sub_categories,slug,' . $id,
                'description' => 'nullable|string',
                'category_id' => 'sometimes|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'is_active' => 'sometimes|boolean'
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle image upload if new image is provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($subcategory->image) {
                    $oldImagePath = public_path('uploads/subcategories/' . $subcategory->image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $file = $request->file('image');

                if ($file->isValid()) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $fileName = time() . '_' . Str::slug($originalName) . '.' . $file->getClientOriginalExtension();

                    // Create directory if it doesn't exist
                    $uploadPath = public_path('uploads/subcategories');
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    // Move file to uploads directory
                    $file->move($uploadPath, $fileName);

                    $subcategory->image = $fileName;
                }
            }

            // Update other fields
            if ($request->has('name')) {
                $subcategory->name = $request->name;
            }

            if ($request->has('slug')) {
                $subcategory->slug = $request->slug;
            }

            if ($request->has('description')) {
                $subcategory->description = $request->description;
            }

            if ($request->has('category_id')) {
                $subcategory->category_id = $request->category_id;
            }

            if ($request->has('is_active')) {
                $subcategory->is_active = $request->is_active;
            }

            // Save changes
            $subcategory->save();

            // Load the parent category relationship
            $subcategory->load('category');

            // Add image URL to response
            $subcategoryData = $subcategory->toArray();
            $subcategoryData['image_url'] = $subcategory->image
                ? url('uploads/subcategories/' . $subcategory->image)
                : null;

            return response()->json([
                'status' => 200,
                'message' => 'Subcategory updated successfully',
                'data' => $subcategoryData,
                'errors' => []
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Find the subcategory
            $subcategory = SubCategory::find($id);

            if (!$subcategory) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Subcategory not found',
                    'errors' => ['Subcategory does not exist']
                ], 404);
            }

            // Check if subcategory has related products (optional)
            // Uncomment if you have a products relationship
            /*
        if ($subcategory->products()->count() > 0) {
            return response()->json([
                'status' => 409,
                'message' => 'Cannot delete subcategory with associated products',
                'errors' => ['This subcategory has ' . $subcategory->products()->count() . ' products linked to it']
            ], 409);
        }
        */

            // Delete the image file if exists
            if ($subcategory->image) {
                $imagePath = public_path('uploads/subcategories/' . $subcategory->image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Delete the subcategory
            $subcategory->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Subcategory deleted successfully',
                'data' => null,
                'errors' => []
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if it's a foreign key constraint error
            if ($e->getCode() == '23000') {
                return response()->json([
                    'status' => 409,
                    'message' => 'Cannot delete subcategory because it has related records',
                    'error' => 'This subcategory is being used in other tables',
                ], 409);
            }

            return response()->json([
                'status' => 500,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong!',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
