<?php

use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MerchantDashboardController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\UsageController;
use App\Http\Controllers\Api\UsageEventController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Merchant Analytics & Dashboard
    Route::get('/merchants/{id}/dashboard', [MerchantDashboardController::class, 'show']);

    // Public / Administrative Tenant & Merchant Endpoints
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::get('/tenants/{tenant}', [TenantController::class, 'show']);
    Route::put('/tenants/{tenant}', [TenantController::class, 'update']);
    Route::post('/merchants', [TenantController::class, 'store']);
    Route::get('/merchants/{tenant}', [TenantController::class, 'show']);
    Route::put('/merchants/{tenant}', [TenantController::class, 'update']);

    // Meter Ingestion Pipeline (Rate-limited at 120 req/min per API key)
    Route::post('/usage', [UsageController::class, 'store'])->middleware('throttle:api');

    // Plans Catalog
    Route::get('/plans', [PlanController::class, 'index']);
    Route::post('/plans', [PlanController::class, 'store']);
    Route::get('/plans/{plan}', [PlanController::class, 'show']);

    // Tenant-Scoped Protected Routes
    Route::middleware(['tenant'])->group(function () {
        // Customer Management
        Route::get('/customers', [CustomerApiController::class, 'index']);
        Route::post('/customers', [CustomerApiController::class, 'store']);
        Route::get('/customers/{customer}', [CustomerApiController::class, 'show']);

        // Subscriptions Lifecycle
        Route::post('/subscriptions', [SubscriptionController::class, 'store']);
        Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show']);
        Route::patch('/subscriptions/{subscription}', [SubscriptionController::class, 'update']);
        Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('/subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume']);

        // Invoicing & Payments
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);

        // High-Throughput Usage Ingestion & Metrics
        Route::middleware(['throttle:meter-ingestion'])->group(function () {
            Route::post('/usage/events', [UsageEventController::class, 'store']);
            Route::post('/usage/batch', [UsageEventController::class, 'batch']);
        });

        Route::get('/usage/events', [UsageEventController::class, 'index']);
        Route::get('/usage/summary', [UsageEventController::class, 'summary']);
    });
});
