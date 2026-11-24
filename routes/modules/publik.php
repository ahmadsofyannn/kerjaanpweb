<?php

use App\Http\Controllers\ParkingMapController;
use App\Http\Controllers\PublikReportController;
use Illuminate\Support\Facades\Route;

// Modul publik: beranda, pencarian plat, peta, kendaraan, prediksi

Route::view('/', 'home')->name('home');

// Peta Parkir Interaktif & Algoritma BFS
Route::get('/peta', [ParkingMapController::class, 'index'])->name('peta');
Route::get('/peta/path', [ParkingMapController::class, 'path'])->name('peta.path');
Route::get('/peta/nearest', [ParkingMapController::class, 'nearest'])->name('peta.nearest');
Route::get('/peta/status', [ParkingMapController::class, 'status'])->name('peta.status');

// Data Kendaraan & Prediksi Berbasis Data Historis
Route::get('/kendaraan', [PublikReportController::class, 'kendaraan'])->name('kendaraan.index');
Route::get('/prediksi', [PublikReportController::class, 'prediksi'])->name('prediksi.index');