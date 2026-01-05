<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NextController;
use App\Http\Controllers\SessionController;
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

    // Phase 5: Item状態遷移
    Route::post('/item/{id}/complete', [ItemController::class, 'complete']);
    Route::post('/item/{id}/continue', [ItemController::class, 'continueItem']);
    Route::post('/item/{id}/defer', [ItemController::class, 'defer']);

    // Phase 3: セッション管理
    Route::post('/session/start', [SessionController::class, 'start']);
    Route::post('/session/stop', [SessionController::class, 'stop']);

    // Phase 4: 選定ロジック（セッション必須）
    Route::middleware('session.active')->group(function () {
        Route::get('/next', [NextController::class, 'show']);
        Route::post('/next/interrupt/accept', [NextController::class, 'acceptInterrupt']);
        Route::post('/next/interrupt/reject', [NextController::class, 'rejectInterrupt']);
    });
});
