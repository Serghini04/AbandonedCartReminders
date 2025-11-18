<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cart/{cart}/complete', [CartController::class, 'completeFromEmail'])
    ->name('cart.complete');