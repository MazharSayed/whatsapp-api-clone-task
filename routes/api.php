<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatroomController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Require Authentication)
Route::middleware('auth:sanctum')->group(function () {
    // User Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Chatroom Routes
    Route::post('/chatrooms', [ChatroomController::class, 'create']);
    Route::get('/chatrooms', [ChatroomController::class, 'list']);
    Route::post('/chatrooms/{id}/join', [ChatroomController::class, 'join']);
    Route::post('/chatrooms/{id}/leave', [ChatroomController::class, 'leave']);

    // Message Routes
    Route::post('/messages', [MessageController::class, 'send']);
    Route::get('/chatrooms/{id}/messages', [MessageController::class, 'list']);
});
