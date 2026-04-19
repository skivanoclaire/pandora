<?php

use Illuminate\Support\Facades\Schedule;

// =====================================================
// SINKRONISASI SIMPEG/SIKARA
// =====================================================

// Sync data dari SIKARA setiap 15 menit
Schedule::command('simpeg:sync')->everyFifteenMinutes()->onOneServer()->withoutOverlapping();

// =====================================================
// PIPELINE ANALITIK
// =====================================================

// Pipeline harian (Tingkat 1) — setiap hari jam 02:00 WITA untuk data H-1
Schedule::command('pipeline:daily')->dailyAt('02:00')->onOneServer()->withoutOverlapping();

// Pipeline bulanan (Tingkat 2+3) — tanggal 5 setiap bulan jam 03:00 WITA
Schedule::command('pipeline:monthly')->monthlyOn(5, '03:00')->onOneServer()->withoutOverlapping();

// =====================================================
// INTEGRITY LAYER (akan diaktifkan di Fase 5)
// =====================================================

// Sync present_rekap → log_presensi_pandora (hash chain) setiap hari jam 23:00
Schedule::command('ledger:sync-presensi --days=2')->dailyAt('23:00')->onOneServer()->withoutOverlapping();

Schedule::command('ledger:anchor-daily')->dailyAt('23:55')->onOneServer();
Schedule::command('ledger:anchor-upgrade')->dailyAt('06:00')->onOneServer();
Schedule::command('ledger:verify')->weeklyOn(0, '02:00')->onOneServer();
