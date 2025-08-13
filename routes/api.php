<?php

use App\Http\Controllers\API\Absensi\AbsensiController;
use App\Http\Controllers\API\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('/absen')->group(function () {
        Route::post('/masuk', [AbsensiController::class, 'masuk']);
        Route::post('/pulang', [AbsensiController::class, 'pulang']);
        Route::post('/izin', [AbsensiController::class, 'izin']);
        Route::get('/cek/{tanggal}', [AbsensiController::class, 'seeAbsensi']);
        Route::get('/see/{user_id}', [AbsensiController::class, 'getAbsensiByUserId']);
    });
});

Route::get('/waktu-server', function () {
    return now()->toDateTimeString();
});
