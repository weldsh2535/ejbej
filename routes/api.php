<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Chat\MessageController;
use App\Http\Controllers\Chat\OfferController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\SubCategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // Your Products routes
    Route::get('/products', [ProductsController::class, 'index']);
    Route::post('/add_products', [ProductsController::class, 'store']);
    // Product Filter Routes
    Route::get('/products/filter', [ProductsController::class, 'filterProducts']);
    Route::get('/products/search', [ProductsController::class, 'searchByName']);

    // Get products by category ID
    Route::get('/products/category/{categoryId}', [ProductsController::class, 'getByCategory']);

    // Get products by seller ID
    Route::get('/sellers/{sellerId}/products', [ProductsController::class, 'getProductsBySeller']);

    // Get products by seller username
    Route::get('/sellers/username/{username}/products', [ProductsController::class, 'getProductsBySellerUsername']);

    // Get seller profile with recent products
    Route::get('/sellers/{sellerId}/profile', [ProductsController::class, 'getSellerProfile']);

    // Get all sellers with product counts
    Route::get('/sellers', [ProductsController::class, 'getAllSellers']);

    // Category
    Route::get('/categories', [CategoryController::class, 'index']);        // Get all categories
    Route::post('/categories', [CategoryController::class, 'store']);       // Create new category
    Route::get('/categories/{id}', [CategoryController::class, 'show']);    // Get single category (optional)
    Route::put('/categories/{id}', [CategoryController::class, 'update']);  // Update category
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']); // Delete category
    Route::get('/categories/{categoryId}/subcategories', [CategoryController::class, 'getByCategory']);

    //SubCategory
    Route::get('/subcategories', [SubCategoryController::class, 'index']);  // 'get' → 'index'
    Route::post('/subcategories', [SubCategoryController::class, 'store']);
    Route::get('/subcategories/{id}', [SubCategoryController::class, 'show']); // If needed
    Route::put('/subcategories/{id}', [SubCategoryController::class, 'update']);
    Route::delete('/subcategories/{id}', [SubCategoryController::class, 'destroy']); // 'delete' → 'dest

    // Chat conversations
    Route::get('/chat/conversations', [ConversationController::class, 'index']);
    Route::get('/chat/conversations/unread-count', [ConversationController::class, 'unreadCount']);
    Route::post('/chat/conversations', [ConversationController::class, 'store']);
    Route::get('/chat/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::put('/chat/conversations/{conversation}', [ConversationController::class, 'update']);

    // Messages
    Route::post('/chat/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::post('/chat/conversations/{conversation}/mark-read', [MessageController::class, 'markAsRead']);
    Route::delete('/chat/messages/{message}', [MessageController::class, 'destroy']);

    // Offers
    Route::post('/chat/conversations/{conversation}/offers', [OfferController::class, 'store']);
    Route::put('/chat/offers/{offer}', [OfferController::class, 'update']);

    // Favorites routes
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']); // Get all favorites
        Route::post('/', [FavoriteController::class, 'store']); // Add to favorites
        Route::get('/stats', [FavoriteController::class, 'stats']); // Get favorites stats
        Route::post('/batch-check', [FavoriteController::class, 'batchCheck']); // Batch check multiple products
        Route::get('/check/{productId}', [FavoriteController::class, 'check']); // Check single product
        Route::delete('/{productId}', [FavoriteController::class, 'destroy']); // Remove from favorites
    });
});
