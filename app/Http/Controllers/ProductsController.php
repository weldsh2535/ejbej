<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryFilterRequest;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SellerResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
     * Get filtered products across all categories
     * 
     * GET /api/products/filter
     */
    public function filterProducts(CategoryFilterRequest $request)
    {
        try {
            $cacheKey = $this->buildCacheKey('filtered_products', 'all', $request);

            $products = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request) {
                $query = Product::query()->with(['images', 'category', 'user']);

                // Apply all filters
                $this->applyFilters($query, $request);

                return $query->paginate($request->input('per_page', 15));
            });

            return response()->json([
                'success' => true,
                'message' => 'Filtered products retrieved successfully',
                'filters' => $request->all(),
                'data' => new ProductCollection($products)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to filter products', [
                'filters' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to filter products',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function searchByName(SearchRequest $request)
    {
        try {
            $products = Product::where('title', 'LIKE', '%' . $request->name . '%')
                ->where('is_active', true)
                ->with(['images', 'category'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'search_term' => $request->name,
                'total' => $products->total(),
                'data' => $products
            ], 200);
        } catch (\Exception $e) {
            Log::error('Search failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed'
            ], 500);
        }
    }

    public function getByCategory(Request $request, $categoryId)
    {
        try {
            // Validate category exists
            $category = Category::findOrFail($categoryId);

            // Get products filtered by category_id
            $products = Product::where('category_id', $categoryId)
                ->where('is_active', true)
                ->with(['images', 'category', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => "Products in category: {$category->name}",
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
                'total' => $products->total(),
                'data' => ProductResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get products by category', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products'
            ], 500);
        }
    }

    /**
     * Get products by category
     * 
     * GET /api/categories/{categoryId}/products
     */
    public function getProductsByCategory(CategoryFilterRequest $request, $categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);

            $cacheKey = $this->buildCacheKey('category_products', $categoryId, $request);

            $products = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($category, $request) {
                $query = Product::query()->with(['images', 'category', 'user']);

                // Filter by category
                if ($request->boolean('include_subcategories', true)) {
                    $categoryIds = $this->getCategoryAndSubcategoryIds($category->id);
                    $query->whereIn('category_id', $categoryIds);
                } else {
                    $query->where('category_id', $category->id);
                }

                // Apply additional filters
                $this->applyFilters($query, $request);

                return $query->paginate($request->input('per_page', 15));
            });

            return response()->json([
                'success' => true,
                'message' => "Products in category: {$category->name}",
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
                'filters' => $request->all(),
                'data' => new ProductCollection($products)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get category products', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    /**
     * Apply filters to query
     */
    private function applyFilters($query, CategoryFilterRequest $request): void
    {
        // Category filter (if category_id is provided directly)
        if ($request->has('category_id') && !$request->route('categoryId')) {
            if ($request->boolean('include_subcategories', true)) {
                $categoryIds = $this->getCategoryAndSubcategoryIds($request->category_id);
                $query->whereIn('category_id', $categoryIds);
            } else {
                $query->where('category_id', $request->category_id);
            }
        }

        // Price range filter
        if ($request->has('min_price') && $request->min_price !== null) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && $request->max_price !== null) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('brand', 'LIKE', "%{$search}%");
            });
        }

        // Location filter
        if ($request->has('location') && !empty($request->location)) {
            $query->where('location', 'LIKE', "%{$request->location}%");
        }

        // Brand filter
        if ($request->has('brand') && !empty($request->brand)) {
            $query->where('brand', $request->brand);
        }

        // Active products only
        $query->where('is_active', true);

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Validate sort column to prevent SQL injection
        $allowedSortColumns = ['price', 'created_at', 'title', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Get category and all subcategory IDs
     */
    private function getCategoryAndSubcategoryIds($categoryId): array
    {
        $ids = [$categoryId];

        $children = Category::where('parent_id', $categoryId)->pluck('id')->toArray();
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getCategoryAndSubcategoryIds($childId));
        }

        return $ids;
    }

    /**
     * Get products by seller ID
     * 
     * GET /api/sellers/{sellerId}/products
     */
    public function getProductsBySeller(Request $request, $sellerId)
    {
        try {
            // Find the seller
            $seller = User::findOrFail($sellerId);

            // Build query
            $query = Product::where('user_id', $sellerId)
                ->where('is_active', true)
                ->with(['images', 'category']);

            // Optional filters
            if ($request->has('search')) {
                $query->where('title', 'LIKE', '%' . $request->search . '%');
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination with more options
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Validate per_page to prevent excessive requests
            $perPage = min($perPage, 100); // Max 100 items per page
            $perPage = max($perPage, 1);    // Min 1 item per page

            $products = $query->paginate($perPage, ['*'], 'page', $page);

            // Get seller stats
            $sellerStats = [
                'total_products' => Product::where('user_id', $sellerId)->count(),
                'active_products' => Product::where('user_id', $sellerId)->where('is_active', true)->count(),
                'joined' => $seller->created_at ? $seller->created_at->diffForHumans() : 'N/A',
                'joined_date' => $seller->created_at ? $seller->created_at->format('Y-m-d') : null,
            ];

            // Enhanced pagination response
            return response()->json([
                'success' => true,
                'message' => "Products by seller: {$seller->name}",
                'seller' => new SellerResource($seller),
                'seller_stats' => $sellerStats,
                'filters' => $request->only(['search', 'min_price', 'max_price', 'category_id']),
                'data' => ProductResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                    'next_page_url' => $products->nextPageUrl(),
                    'prev_page_url' => $products->previousPageUrl(),
                    'first_page_url' => $products->url(1),
                    'last_page_url' => $products->url($products->lastPage()),
                    'has_more_pages' => $products->hasMorePages(),
                ],
                'links' => [
                    'first' => $products->url(1),
                    'last' => $products->url($products->lastPage()),
                    'prev' => $products->previousPageUrl(),
                    'next' => $products->nextPageUrl(),
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seller not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get seller products', [
                'seller_id' => $sellerId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products'
            ], 500);
        }
    }

    /**
     * Get products by seller username
     * 
     * GET /api/sellers/username/{username}/products
     */
    public function getProductsBySellerUsername(Request $request, $username)
    {
        try {
            $seller = User::where('username', $username)
                ->orWhere('email', $username)
                ->firstOrFail();

            return $this->getProductsBySeller($request, $seller->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seller not found'
            ], 404);
        }
    }

    /**
     * Get seller details with products
     * 
     * GET /api/sellers/{sellerId}/profile
     */
    public function getSellerProfile($sellerId)
    {
        try {
            $seller = User::withCount(['products', 'activeProducts'])
                ->findOrFail($sellerId);

            // Get recent products (limit 5)
            $recentProducts = Product::where('user_id', $sellerId)
                ->where('is_active', true)
                ->with(['images', 'category'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'seller' => new SellerResource($seller),
                'stats' => [
                    'total_products' => $seller->products_count,
                    'active_products' => $seller->active_products_count,
                    'member_since' => $seller->created_at->format('Y-m-d'),
                ],
                'recent_products' => ProductResource::collection($recentProducts)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seller not found'
            ], 404);
        }
    }

    /**
     * Get all sellers with product counts
     * 
     * GET /api/sellers
     */
    public function getAllSellers(Request $request)
    {
        try {
            $sellers = User::whereHas('products', function ($query) {
                $query->where('is_active', true);
            })
                ->withCount(['products', 'activeProducts'])
                ->orderBy('active_products_count', 'desc')
                ->paginate($request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'total' => $sellers->total(),
                'data' => SellerResource::collection($sellers),
                'pagination' => [
                    'current_page' => $sellers->currentPage(),
                    'per_page' => $sellers->perPage(),
                    'total' => $sellers->total(),
                    'last_page' => $sellers->lastPage(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sellers'
            ], 500);
        }
    }

    /**
     * Build cache key from request parameters
     */
    private function buildCacheKey($prefix, $identifier, $request): string
    {
        $params = [
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 15),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'search' => $request->input('search'),
            'location' => $request->input('location'),
            'brand' => $request->input('brand'),
            'category_id' => $request->input('category_id'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
            'include_subcategories' => $request->input('include_subcategories', true),
        ];

        // Remove null and empty values
        $params = array_filter($params, function ($value) {
            return !is_null($value) && $value !== '';
        });

        $paramString = md5(json_encode($params));

        return "{$prefix}_{$identifier}_{$paramString}";
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
