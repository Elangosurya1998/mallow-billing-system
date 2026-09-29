<?php

use App\Http\Controllers\ApiConsoleController;
use App\Http\Controllers\MerchantDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MerchantDashboardController::class, 'index'])->name('home');
Route::get('/merchants', [MerchantDashboardController::class, 'index'])->name('merchants.index');
Route::get('/merchants/{merchant}/dashboard', [MerchantDashboardController::class, 'show'])->name('merchants.dashboard');
Route::get('/console', [ApiConsoleController::class, 'index'])->name('api.console');
Route::get('/merchants/{merchant}/console', [ApiConsoleController::class, 'merchantConsole'])->name('merchants.console');
