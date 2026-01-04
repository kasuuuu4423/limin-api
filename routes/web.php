<?php

use App\Http\Controllers\TestUIController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// テストUI
Route::get('/test-ui', [TestUIController::class, 'index'])->name('test-ui');
