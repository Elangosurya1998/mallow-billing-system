<?php

namespace App\Providers;

use App\Models\Plan;
use App\Observers\PlanObserver;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Plans\PlanPricingService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(PaymentGatewayInterface::class, FakePaymentGateway::class);
        $this->app->singleton(PlanPricingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Plan::observe(PlanObserver::class);
    }
}
