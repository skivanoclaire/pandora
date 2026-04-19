<?php

use App\Http\Controllers\AnalitikController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardPimpinanController;
use App\Http\Controllers\KehadiranController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\IntegrityController;
use App\Http\Controllers\LiterasiDataController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\SinkronisasiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Route::get('/dashboard/pimpinan', [DashboardPimpinanController::class, 'index'])->name('dashboard.pimpinan');

    Route::get('/master/instansi', [MasterController::class, 'instansi']);
    Route::get('/master/pegawai', [MasterController::class, 'pegawai']);
    Route::get('/master/geofence', [MasterController::class, 'geofence']);
    Route::post('/master/geofence/zones', [MasterController::class, 'storeZone'])->name('master.geofence.store-zone');
    Route::delete('/master/geofence/zones/{id}', [MasterController::class, 'destroyZone'])->name('master.geofence.destroy-zone');

    Route::get('/master/whitelist', [MasterController::class, 'whitelist'])->name('master.whitelist');
    Route::post('/master/whitelist', [MasterController::class, 'storeWhitelist'])->name('master.whitelist.store');
    Route::delete('/master/whitelist/{id}', [MasterController::class, 'destroyWhitelist'])->name('master.whitelist.destroy');

    Route::get('/kehadiran/rekap', [KehadiranController::class, 'rekap']);
    Route::get('/kehadiran/log', [KehadiranController::class, 'log']);

    Route::get('/analitik/tren', [AnalitikController::class, 'tren']);
    Route::get('/analitik/tren/{tanggal}', [AnalitikController::class, 'trenDetail'])->name('analitik.tren.detail');
    Route::get('/analitik/tren/{tanggal}/dinas', [AnalitikController::class, 'trenDinas'])->name('analitik.tren.dinas');
    Route::get('/analitik/tren/{tanggal}/ijin/{kategori}', [AnalitikController::class, 'trenIjin'])->name('analitik.tren.ijin');
    Route::get('/analitik/tren/{tanggal}/tanpa-keterangan', [AnalitikController::class, 'trenTanpaKeterangan'])->name('analitik.tren.tanpa-keterangan');
    Route::get('/analitik/anomali', [AnalitikController::class, 'anomali']);
    Route::get('/analitik/anomali/export-pdf', [AnalitikController::class, 'exportAnomaliPdf'])->name('analitik.anomali.export');
    Route::get('/analitik/anomali/{id}', [AnalitikController::class, 'detailAnomali'])->name('analitik.anomali.detail');
    Route::patch('/analitik/anomali/{id}/review', [AnalitikController::class, 'reviewAnomali'])->name('analitik.anomali.review');
    Route::get('/analitik/clustering', [AnalitikController::class, 'clustering']);

    Route::get('/sinkronisasi', [SinkronisasiController::class, 'index']);

    Route::middleware('role:admin')->prefix('pengaturan')->group(function () {
        Route::get('/', [PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::get('/audit-trail', [PengaturanController::class, 'auditTrail'])->name('pengaturan.audit-trail');
        Route::get('/users', [UserController::class, 'index'])->name('pengaturan.users');
        Route::post('/users', [UserController::class, 'store'])->name('pengaturan.users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('pengaturan.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('pengaturan.users.destroy');
    });

    Route::prefix('literasi-data')->group(function () {
        Route::get('/', [LiterasiDataController::class, 'index'])->name('literasi-data.index');
        Route::get('/cari', [LiterasiDataController::class, 'search'])->name('literasi-data.search');
        Route::get('/{category}', [LiterasiDataController::class, 'category'])->name('literasi-data.category');
        Route::get('/{category}/{concept}', [LiterasiDataController::class, 'show'])->name('literasi-data.show');
    });

    Route::prefix('integritas')->group(function () {
        Route::get('/', [IntegrityController::class, 'index'])->name('integritas.index');
        Route::get('/download/{date}.ots', [IntegrityController::class, 'downloadProof'])->name('integritas.download');
        Route::get('/verify/{date}', [IntegrityController::class, 'verifyDate'])->name('integritas.verify');
    });
});
