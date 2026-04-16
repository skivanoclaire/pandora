<?php

use App\Http\Controllers\AnalitikController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KehadiranController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\SinkronisasiController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/master/instansi', [MasterController::class, 'instansi']);
    Route::get('/master/pegawai', [MasterController::class, 'pegawai']);
    Route::get('/master/geofence', [MasterController::class, 'geofence']);

    Route::get('/kehadiran/rekap', [KehadiranController::class, 'rekap']);
    Route::get('/kehadiran/log', [KehadiranController::class, 'log']);

    Route::get('/analitik/tren', [AnalitikController::class, 'tren']);
    Route::get('/analitik/anomali', [AnalitikController::class, 'anomali']);
    Route::get('/analitik/clustering', [AnalitikController::class, 'clustering']);

    Route::get('/sinkronisasi', [SinkronisasiController::class, 'index']);

    Route::get('/pengaturan', [PengaturanController::class, 'index'])->middleware('role:admin');
});
