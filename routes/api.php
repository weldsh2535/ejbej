<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Chat\MessageController;
use App\Http\Controllers\Chat\OfferController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\SubCategoryController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;

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

    // Your Products routes
    Route::get('/products', [ProductsController::class, 'index']);
    Route::post('/add_products', [ProductsController::class, 'store']);

    Route::get('/categorys', [CategoryController::class, 'get']);
    Route::post('/add_categorys', [CategoryController::class, 'store']);
    Route::put('/categorys/{id}', [CategoryController::class, 'update']);
    Route::delete('/categorys/{id}', [CategoryController::class, 'delete']);

    Route::get('/sub_categorys', [SubCategoryController::class, 'get']);
    Route::post('/add_sub_categorys', [SubCategoryController::class, 'store']);
    Route::put('/sub_categorys/{id}', [SubCategoryController::class, 'update']);
    Route::delete('/sub_categorys/{id}', [SubCategoryController::class, 'delete']);

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
});
