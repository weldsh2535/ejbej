<?php
// app/Http/Controllers/FavoriteController.php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use App\Http\Requests\FavoriteRequest;
use App\Http\Resources\FavoriteCollection;
use App\Http\Resources\FavoriteResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    /**
     * Get user's favorites with pagination
     * 
     * GET /api/favorites
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $perPage = $request->input('per_page', 15);
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            // Get paginated favorites
            $favorites = $user->favorites()
                ->with(['product' => function ($query) {
                    $query->with(['images', 'category']);
                }])
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Favorites retrieved successfully',
                'data' => new FavoriteCollection($favorites) // This expects a paginated result
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve favorites', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve favorites',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    /**
     * Add product to favorites
     * 
     * POST /api/favorites
     */
    public function store(FavoriteRequest $request)
    {
        try {
            $user = auth()->user();
            $productId = $request->product_id;

            // Check if already favorited
            $exists = Favorite::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product already in favorites'
                ], 409);
            }

            DB::beginTransaction();

            // Create favorite
            $favorite = Favorite::create([
                'user_id' => $user->id,
                'product_id' => $productId
            ]);

            // Load product relationship
            $favorite->load('product');

            DB::commit();

            // Clear user's favorites cache
            $this->clearUserFavoritesCache($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Product added to favorites',
                'data' => new FavoriteResource($favorite)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to add favorite', [
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add to favorites',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove product from favorites
     * 
     * DELETE /api/favorites/{productId}
     */
    public function destroy($productId)
    {
        try {
            $user = auth()->user();

            DB::beginTransaction();

            $favorite = Favorite::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Favorite not found'
                ], 404);
            }

            // Delete favorite
            $favorite->delete();

            DB::commit();

            // Clear user's favorites cache
            $this->clearUserFavoritesCache($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Product removed from favorites'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to remove favorite', [
                'user_id' => auth()->id(),
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove from favorites',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if product is favorited
     * 
     * GET /api/favorites/check/{productId}
     */
    public function check($productId)
    {
        try {
            $user = auth()->user();

            // Simple cache key
            $cacheKey = "user_{$user->id}_favorite_check_{$productId}";

            $isFavorited = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user, $productId) {
                return Favorite::where('user_id', $user->id)
                    ->where('product_id', $productId)
                    ->exists();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => (int) $productId,
                    'is_favorited' => $isFavorited
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to check favorite status', [
                'user_id' => auth()->id(),
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check favorite status'
            ], 500);
        }
    }

    /**
     * Get favorites count for multiple products
     * 
     * POST /api/favorites/batch-check
     */
    public function batchCheck(Request $request)
    {
        try {
            $request->validate([
                'product_ids' => 'required|array',
                'product_ids.*' => 'integer|exists:products,id'
            ]);

            $user = auth()->user();
            $productIds = $request->product_ids;

            // Get all favorited product IDs in one query
            $favoritedIds = Favorite::where('user_id', $user->id)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->toArray();

            $result = [];
            foreach ($productIds as $productId) {
                $result[$productId] = in_array($productId, $favoritedIds);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to batch check favorites', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check favorites'
            ], 500);
        }
    }

    /**
     * Get favorites statistics
     * 
     * GET /api/favorites/stats
     */
    public function stats()
    {
        try {
            $user = auth()->user();

            $cacheKey = "user_{$user->id}_favorites_stats";

            $stats = Cache::remember($cacheKey, now()->addHours(1), function () use ($user) {
                $favorites = $user->favorites()->with('product.category')->get();

                // Calculate statistics
                $totalFavorites = $favorites->count();

                // Group by category
                $categoryStats = [];
                foreach ($favorites as $favorite) {
                    if ($favorite->product && $favorite->product->category) {
                        $catId = $favorite->product->category_id;
                        $catName = $favorite->product->category->name;

                        if (!isset($categoryStats[$catId])) {
                            $categoryStats[$catId] = [
                                'category_id' => $catId,
                                'category_name' => $catName,
                                'count' => 0
                            ];
                        }
                        $categoryStats[$catId]['count']++;
                    }
                }

                // Most recent favorites
                $mostRecent = $favorites->sortByDesc('created_at')->take(5)->map(function ($fav) {
                    return [
                        'product_id' => $fav->product_id,
                        'product_name' => $fav->product->title ?? 'Unknown',
                        'product_image' => $fav->product->primary_image_url ?? null,
                        'added_at' => $fav->created_at->diffForHumans(),
                        'added_at_raw' => $fav->created_at->format('Y-m-d H:i:s')
                    ];
                })->values();

                return [
                    'total_favorites' => $totalFavorites,
                    'unique_products' => $favorites->pluck('product_id')->unique()->count(),
                    'unique_categories' => count($categoryStats),
                    'category_breakdown' => array_values($categoryStats),
                    'most_recent' => $mostRecent,
                    'favorited_product_ids' => $favorites->pluck('product_id')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get favorites stats', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get favorites statistics'
            ], 500);
        }
    }

    /**
     * Get favorited products with full details
     * 
     * GET /api/favorites/products
     */
    public function products(Request $request)
    {
        try {
            $user = auth()->user();

            $perPage = $request->input('per_page', 15);

            $favoritedProductIds = $user->favorites()->pluck('product_id');

            $products = Product::whereIn('id', $favoritedProductIds)
                ->with(['images', 'category', 'user'])
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Favorite products retrieved successfully',
                'data' => $products
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get favorite products', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get favorite products'
            ], 500);
        }
    }

    /**
     * Clear user's favorites cache
     * 
     * @param int $userId
     * @return void
     */
    private function clearUserFavoritesCache($userId)
    {
        // For file/database cache, we need to clear specific keys
        // Method 1: Increment version number
        $versionKey = "user_{$userId}_favorites_version";
        $version = Cache::get($versionKey, 1);
        Cache::forever($versionKey, $version + 1);

        // Method 2: Clear specific known keys (alternative approach)
        $keysToForget = [
            "user_favorites_{$userId}_page_1_per_15",
            "user_{$userId}_favorites_stats",
            "user_{$userId}_favorites_products",
        ];

        foreach ($keysToForget as $key) {
            Cache::forget($key);
        }

        Log::info('Cleared favorites cache for user', ['user_id' => $userId]);
    }

    /**
     * Get cache key with version
     * 
     * @param int $userId
     * @param string $key
     * @return string
     */
    private function getCacheKey($userId, $key)
    {
        $version = Cache::get("user_{$userId}_favorites_version", 1);
        return "user_{$userId}_favorites_v{$version}_{$key}";
    }
}
