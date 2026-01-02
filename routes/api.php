<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

// 認証不要
Route::post('/auth/token', [AuthController::class, 'token']);
Route::get('/health', [HealthController::class, 'show']);

// 認証必須
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Phase 2: Capture + Item基本操作
    Route::post('/capture', [CaptureController::class, 'store']);
    Route::delete('/item/{id}', [ItemController::class, 'destroy']);
    Route::post('/item/{id}/next-action', [ItemController::class, 'updateNextAction']);

    // Phase 3以降で追加するエンドポイント
    // Route::post('/session/start', [SessionController::class, 'start']);
    // Route::post('/session/stop', [SessionController::class, 'stop']);
});
