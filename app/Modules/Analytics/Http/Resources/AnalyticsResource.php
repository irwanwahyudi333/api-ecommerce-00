<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{
 *     totalRevenue: float,
 *     totalRefunds: float,
 *     totalShops: int,
 *     totalVendors: int,
 *     todaysRevenue: float,
 *     totalOrders: int,
 *     newCustomers: int,
 *     totalYearSaleByMonth: array<int, array{month: string, total: float}>,
 *     todayTotalOrderByStatus: array<string, mixed>,
 *     weeklyTotalOrderByStatus: array<string, mixed>,
 *     monthlyTotalOrderByStatus: array<string, mixed>,
 *     yearlyTotalOrderByStatus: array<string, mixed>,
 * } $resource
 */
class AnalyticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_revenue' => $this->resource['totalRevenue'],
            'total_refunds' => $this->resource['totalRefunds'],
            'total_shops' => $this->resource['totalShops'],
            'total_vendors' => $this->resource['totalVendors'],
            'todays_revenue' => $this->resource['todaysRevenue'],
            'total_orders' => $this->resource['totalOrders'],
            'new_customers' => $this->resource['newCustomers'],
            'total_year_sale_by_month' => $this->resource['totalYearSaleByMonth'],
            'today_total_order_by_status' => $this->resource['todayTotalOrderByStatus'],
            'weekly_total_order_by_status' => $this->resource['weeklyTotalOrderByStatus'],
            'monthly_total_order_by_status' => $this->resource['monthlyTotalOrderByStatus'],
            'yearly_total_order_by_status' => $this->resource['yearlyTotalOrderByStatus'],
        ];
    }
}
