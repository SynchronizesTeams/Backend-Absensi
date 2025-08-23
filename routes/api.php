<?php

use App\Http\Controllers\API\Absensi\AbsensiController;
use App\Http\Controllers\API\Admin\AdminController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Log\LogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('/v1')->group(function () {
        Route::prefix('/absen')->group(function () {
            Route::post('/masuk', [AbsensiController::class, 'masuk']);
            Route::post('/pulang', [AbsensiController::class, 'pulang']);
            Route::post('/izin', [AbsensiController::class, 'izin']);
            Route::get('/cek/{tanggal}', [AbsensiController::class, 'seeAbsensi']);
            Route::get('/see/{user_id}', [AbsensiController::class, 'getAbsensiByUserId']);
        });
        Route::prefix('/admin')->group(function () {
            Route::get('/export-absensi', [AdminController::class, 'export'])->name('export.absensi');
            Route::get('/count-user', [AdminController::class, 'CountUser']);
            Route::get('/count-users-by-role/{role}', [AdminController::class, 'countUs`ersByRole']);
        });
        Route::prefix('/log')->group(function () {
            Route::get('/user/{user_id}', [LogController::class, 'logUser']);
        });
    });

    Route::prefix('v2')->group(function () {
        Route::prefix('/absen')->group(function () {
            Route::post('/masuk', [AbsensiController::class, 'masukV2']);
        });
    });



});


Route::get('/waktu-server', function () {
    return now()->toDateTimeString();
});
