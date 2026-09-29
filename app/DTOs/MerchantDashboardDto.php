<?php

declare(strict_types=1);

namespace App\DTOs;

final class MerchantDashboardDto
{
    public private(set) string $merchantId;

    public private(set) string $merchantName;

    public private(set) string $currency;

    public private(set) string $asOfDate;

    public private(set) array $cycleUsage;

    public private(set) array $projectedOverageRevenue;

    public private(set) array $topCustomersByUsage;

    public private(set) array $churnRiskCustomers;

    public function __construct(
        string $merchantId,
        string $merchantName,
        string $currency,
        string $asOfDate,
        array $cycleUsage,
        array $projectedOverageRevenue,
        array $topCustomersByUsage,
        array $churnRiskCustomers,
    ) {
        $this->merchantId = $merchantId;
        $this->merchantName = $merchantName;
        $this->currency = $currency;
        $this->asOfDate = $asOfDate;
        $this->cycleUsage = $cycleUsage;
        $this->projectedOverageRevenue = $projectedOverageRevenue;
        $this->topCustomersByUsage = $topCustomersByUsage;
        $this->churnRiskCustomers = $churnRiskCustomers;
    }

    public function toArray(): array
    {
        return [
            'merchant_id' => $this->merchantId,
            'merchant_name' => $this->merchantName,
            'currency' => $this->currency,
            'as_of_date' => $this->asOfDate,
            'cycle_usage' => $this->cycleUsage,
            'projected_overage_revenue' => $this->projectedOverageRevenue,
            'top_customers_by_usage' => $this->topCustomersByUsage,
            'churn_risk_customers' => $this->churnRiskCustomers,
        ];
    }
}
