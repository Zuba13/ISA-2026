<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/videos', [App\Http\Controllers\VideoController::class, 'index']);
Route::get('/videos/{id}', [App\Http\Controllers\VideoController::class, 'show']);
Route::get('/videos/{id}/stream', [App\Http\Controllers\VideoController::class, 'stream']);
Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
Route::get('/users/{id}', [App\Http\Controllers\UserController::class, 'show']);
Route::get('/mq/benchmark', [App\Http\Controllers\MqBenchmarkController::class, 'benchmark']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify');

// Comment routes
Route::get('/videos/{videoId}/comments', [App\Http\Controllers\CommentController::class, 'index']);
Route::get('/likes/{likeableType}/{likeableId}', [App\Http\Controllers\LikeController::class, 'index']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Watch Party routes
Route::get('/rooms', [App\Http\Controllers\WatchPartyController::class, 'getActiveRooms']);
Route::get('/rooms/{id}', [App\Http\Controllers\WatchPartyController::class, 'show']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/videos/{videoId}/comments', [App\Http\Controllers\CommentController::class, 'store']);
    Route::put('/comments/{id}', [App\Http\Controllers\CommentController::class, 'update']);
    Route::delete('/comments/{id}', [App\Http\Controllers\CommentController::class, 'destroy']);
    Route::post('/likes/toggle', [App\Http\Controllers\LikeController::class, 'toggle']);

    // Watch Party / Room routes (authenticated)
    Route::post('/rooms', [App\Http\Controllers\WatchPartyController::class, 'createRoom']);
    Route::post('/rooms/join', [App\Http\Controllers\WatchPartyController::class, 'joinRoom']);
    Route::post('/rooms/{id}/leave', [App\Http\Controllers\WatchPartyController::class, 'leaveRoom']);
    Route::post('/rooms/{id}/start-video', [App\Http\Controllers\WatchPartyController::class, 'startVideo']);
    Route::post('/rooms/{id}/sync-video', [App\Http\Controllers\WatchPartyController::class, 'syncVideo']);
});
