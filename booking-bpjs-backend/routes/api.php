<?php

use App\Http\Controllers\Api\SimrsLiveAntreanController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AntreanListController;
use App\Http\Controllers\Api\PublicAntreanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::get('/antrean/public-list', [PublicAntreanController::class, 'list']);
Route::get('/antrean/nomor-antrean', [PublicAntreanController::class, 'nomorAntrean']);
Route::post('/antrean/ambil', [PublicAntreanController::class, 'ambilAntrean']);
Route::get('/antrean/cek', [PublicAntreanController::class, 'cekAntrean']);
Route::get('/antrean/live-rencana-kontrol', [SimrsLiveAntreanController::class, 'listRencanaKontrol']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // Route::get('/antrean/poli', [AntreanController::class, 'poli']);
    Route::get('/antrean/list', [AntreanListController::class, 'index']);
});

Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        return 'Koneksi DB sukses!';
    } catch (\Exception $e) {
        return 'Koneksi gagal: ' . $e->getMessage();
    }
});
