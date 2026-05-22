<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\IncidentsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Models\Facultad;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index']);

Route::get('/facultades', function () {
    return response()->json(
        Facultad::query()
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get()
    );
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::patch('/tasks/{id}', [TaskController::class, 'update']);
    Route::get('/incidents', [IncidentsController::class, 'index']);
    Route::post('/incidents', [IncidentsController::class, 'store']);
    Route::get('/notifications', [NotificationsController::class, 'index']);
    Route::post('/notifications/{notificationId}/read', [NotificationsController::class, 'markAsRead']);
    Route::post('/profile/image', [UserController::class, 'updateProfileImage']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
