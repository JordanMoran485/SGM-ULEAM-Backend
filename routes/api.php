<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IncidentsController;


Route::get('/users', [UserController::class, 'index']);

Route::get('incidents', [IncidentsController::class, 'index']);
Route::post('incidents', [IncidentsController::class, 'store']);
    Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});