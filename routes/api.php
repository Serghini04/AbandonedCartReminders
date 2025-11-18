<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::prefix('cart')->group(function () {
    Route::post('/add-product', [CartController::class, 'addProduct']);
    Route::get('/active', [CartController::class, 'getCart']);
    Route::get('/{cart}/complete', [CartController::class, 'completeFromEmail']);
    Route::post('/{cartId}/finalize', [CartController::class, 'finalizeCart']);
});