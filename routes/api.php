<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Models\Carrera;
use App\Models\Facultad;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index']);

Route::get('/tasks', [TaskController::class, 'index']);
Route::get('/incidents', [TaskController::class, 'index']);

Route::get('/facultades', function () {
    return response()->json(
        Facultad::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
    );
});

Route::get('/carreras', function () {
    return response()->json(
        Carrera::query()
            ->select('id', 'name', 'facultad_id')
            ->orderBy('name')
            ->get()
    );
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/incidents', [TaskController::class, 'store']);
    Route::post('/profile/image', [UserController::class, 'updateProfileImage']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
