<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BasketWebController;

Route::get('/', function () {
    return redirect('/basket');
});

Route::get('/basket', [BasketWebController::class, 'showForm']);
Route::post('/basket', [BasketWebController::class, 'calculate']);
