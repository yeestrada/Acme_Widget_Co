<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BasketWebController;

Route::get('/', [BasketWebController::class, 'showForm']);
Route::post('/basket', [BasketWebController::class, 'calculate']);
Route::post('/basket/calculate', [BasketWebController::class, 'calculateAjax'])->name('basket.calculate');
