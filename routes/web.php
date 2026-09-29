<?php

use App\Http\Controllers\ApiConsoleController;
use App\Http\Controllers\MerchantDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MerchantDashboardController::class, 'index'])->name('home');

// Merchant Management & Lifecycle
Route::get('/merchants', [MerchantDashboardController::class, 'index'])->name('merchants.index');
Route::get('/merchants/create', [MerchantDashboardController::class, 'create'])->name('merchants.create');
Route::post('/merchants', [MerchantDashboardController::class, 'store'])->name('merchants.store');
Route::get('/merchants/{merchant}/edit', [MerchantDashboardController::class, 'edit'])->name('merchants.edit');
Route::put('/merchants/{merchant}', [MerchantDashboardController::class, 'update'])->name('merchants.update');
Route::get('/merchants/{merchant}/dashboard', [MerchantDashboardController::class, 'show'])->name('merchants.dashboard');

// Customer Management under Merchant
Route::get('/merchants/{merchant}/customers/create', [MerchantDashboardController::class, 'createCustomer'])->name('merchants.customers.create');
Route::post('/merchants/{merchant}/customers', [MerchantDashboardController::class, 'storeCustomer'])->name('merchants.customers.store');

// API Web Console
Route::get('/console', [ApiConsoleController::class, 'index'])->name('api.console');
Route::get('/merchants/{merchant}/console', [ApiConsoleController::class, 'merchantConsole'])->name('merchants.console');
