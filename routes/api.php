<?php

use App\Http\Controllers\API\Absensi\AbsensiController;
use App\Http\Controllers\API\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/presensi/{user_id}', [AbsensiController::class, 'presensi']);
});

Route::get('/waktu-server', function () {
    return now()->toDateTimeString();
});
