<?php

use App\Http\Controllers\Authcontroller;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by    the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::controller(AuthController::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', 'login'); 
        Route::get('/refresh', 'refreshToken'); 
    });
});

Route::middleware('auth:api')->group(function () {
    // Ruta para obtener el perfil del usuario
    Route::controller(ProductController::class)->group(function () {
        Route::prefix('product')->group(function () {
            Route::post('/register', 'registerProduct'); 
            Route::put('/update/state', 'udpateState'); 
            Route::get('/list', 'listProduct'); 
        });
    });

    Route::controller(OrderController::class)->group(function () {
        Route::prefix('order')->group(function () {
            Route::post('/register', 'registerOrder'); 
            Route::post('/update', 'updateOrder'); 
            Route::get('/product/list', 'listProduct'); 
            Route::post('/list', 'updateOrder'); 
            Route::post('/cancel', 'cancelOrder'); 

        });
    });

    Route::controller(UserController::class)->group(function () {
        Route::prefix('user')->group(function () {
            Route::post('/register', 'registerUser'); 
            
        });
    });
});

Route::get('/test', function () {
    return 'Hello World';
});




