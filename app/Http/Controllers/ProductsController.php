<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductsController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService
    ) {}

    /**
     * Display a paginated listing of products.
     *
     * @param Request $request
     * @return ProductCollection
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $products = Product::with(['user', 'images', 'category'])
            ->when($request->has('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->category_id);
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($request->has('min_price'), function ($query) use ($request) {
                $query->where('price', '>=', $request->min_price);
            })
            ->when($request->has('max_price'), function ($query) use ($request) {
                $query->where('price', '<=', $request->max_price);
            })
            ->when($request->has('location'), function ($query) use ($request) {
                $query->where('location', 'like', "%{$request->location}%");
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->withQueryString(); // Preserve query parameters in pagination links

        return new ProductCollection($products);
    }

    /**
     * Store a newly created product.
     *
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create product
            $product = Product::create($request->safe()->except(['images', 'primary_image_index']));

            // Handle image uploads
            if ($request->hasFile('images')) {
                $this->handleImageUploads($product, $request);
            }

            DB::commit();

            // Load relationships for response
            $product->load(['images', 'category', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => new ProductResource($product)
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }


    private function handleImageUploads(Product $product, StoreProductRequest $request): void
    {
        $images = $request->file('images');

        // Convert single image to array
        if (!is_array($images)) {
            $images = [$images];
        }

        // Filter out null values and reindex
        $images = array_values(array_filter($images));

        if (empty($images)) {
            Log::warning('No valid images to upload');
            return;
        }

        // Upload images using the service
        $uploadedImages = $this->imageService->uploadMultiple($images, 'products/' . $product->id);

        // Save to database
        foreach ($uploadedImages as $index => $imageData) {
            $product->images()->create([
                'product_id' => $product->id,
                'path' => $imageData['path'],
                'filename' => $imageData['filename'],
                'mime_type' => $imageData['mime_type'],
                'size' => $imageData['size'],
                'sort_order' => $index,
                'is_primary' => $index === (int)($request->input('primary_image_index', 0)),
                'alt_text' => $request->input('title') // Optional: use product title as alt text
            ]);
        }

        Log::info('Images saved to database', [
            'product_id' => $product->id,
            'count' => count($uploadedImages)
        ]);
    }
}
