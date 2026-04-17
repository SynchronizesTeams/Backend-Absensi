<?php

use App\Http\Controllers\API\Absensi\AbsensiController;
use App\Http\Controllers\API\Absensi\V2\AbsensiControllerV2;
use App\Http\Controllers\API\Admin\AdminController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Laporan\LaporanController;
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
            Route::post('/masuk', [AbsensiControllerV2::class, 'masuk']);
            Route::post('/pulang', [AbsensiControllerV2::class, 'pulang']);
            Route::post('/izin', [AbsensiController::class, 'izin']);
            Route::get('/cek/{tanggal}', [AbsensiController::class, 'seeAbsensi']);
            Route::get('/see/{user_id}', [AbsensiController::class, 'getAbsensiByUserId']);
            Route::get('/recap/{user_id}', [AbsensiController::class, 'recapAbsensi']);

            Route::get('/cek-masuk/{user_id}', [AbsensiController::class, 'cekAbsenMasuk']);
            Route::get('/cek-pulang/{user_id}', [AbsensiController::class, 'cekAbsenPulang']);
            Route::get('/cek-izin/{user_id}', [AbsensiController::class, 'cekAbsenIzin']);
        });
        Route::prefix("/laporan")->group(function () {
            Route::post("/create", [LaporanController::class, 'store']);
            Route::post("/update/{id}", [LaporanController::class, 'update']);
            Route::get("/show", [LaporanController::class, 'index']);
            Route::get("/show/{id}", [LaporanController::class, 'show']);
            Route::get("/show/user/{id}", [LaporanController::class, 'showLaporanByUser']);
            Route::get("/show/date", [LaporanController::class, 'showDailyLaporan']);
            Route::delete("/delete/{id}", [LaporanController::class, 'destroy']);
        });
        Route::prefix('/admin')->group(function () {
            Route::get('/export-absensi', [AdminController::class, 'export'])->name('export.absensi');
            Route::get('/export-recap-bulanan', [AdminController::class, 'exportSummary']);
            Route::get('/count-user', [AdminController::class, 'CountUser']);
            Route::get('/count-user/{role}', [AdminController::class, 'countUsersByRole']);
            Route::get('/see-user/{role}', [AdminController::class, 'seeUserByRole']);

            // User CRUD
            Route::post('/users/batch-create', [AdminController::class, 'batchStore']);
            Route::get('/users', [AdminController::class, 'index']);
            Route::post('/users', [AdminController::class, 'store']);
            Route::get('/users/{id}', [AdminController::class, 'show']);
            Route::post('/users/{id}', [AdminController::class, 'update']);
            Route::delete('/users/{id}', [AdminController::class, 'destroy']);

            //Absensi CRUD
            Route::put('/absensi/{id}', [AdminController::class, 'updateAbsensi']);
            Route::delete('/absensi/{id}', [AdminController::class, 'deleteAbsensi']);
        });
        Route::prefix('/log')->group(function () {
            Route::get('/user/{user_id}', [LogController::class, 'logUser']);
        });
        Route::prefix('/user')->group(function () {
            Route::get('/info/{user_id}', [AuthController::class, 'getUserInfo']);
            Route::post('/edit/{user_id}', [AuthController::class, 'editUserInfo']);
            Route::post('/edit-password/{user_id}', [AuthController::class, 'editPassword']);
        });

    });

    Route::prefix('v2')->group(function () {
        Route::prefix('/absen')->group(function () {
            Route::post('/masuk', [AbsensiControllerV2::class, 'masuk']);
            Route::post('/pulang', [AbsensiControllerV2::class, 'pulang']);
        });
    });



});


Route::get('/waktu-server', function () {
    return now()->toDateTimeString();
});
