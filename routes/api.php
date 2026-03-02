<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// Protected API routes
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // User management
    Route::apiResource('users', UserController::class);
});

Route::get('/products', [ProductsController::class, 'get']);
Route::post('/add_products', [ProductsController::class, 'store']);

Route::get('/categorys', [  CategoryController::class, 'get']);
Route::post('/add_categorys', [CategoryController::class, 'store']);
Route::put('/categorys/{id}', [CategoryController::class, 'update']);
Route::delete('/categorys/{id}', [CategoryController::class, 'delete']);

Route::get('/sub_categorys', [SubCategoryController::class, 'get']);
Route::post('/add_sub_categorys', [SubCategoryController::class, 'store']);
Route::put('/sub_categorys/{id}', [SubCategoryController::class, 'update']);
Route::delete('/sub_categorys/{id}', [SubCategoryController::class, 'delete']);