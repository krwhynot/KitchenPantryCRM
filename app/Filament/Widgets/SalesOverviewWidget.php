<?php

namespace App\Filament\Widgets;

use App\Services\ReportingService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $reportingService = app(ReportingService::class);
        $metrics = $reportingService->getSalesMetrics($this->getFilterData());
        
        return [
            Stat::make('Total Revenue', '$' . number_format($metrics['total_revenue'], 0))
                ->description('From ' . $metrics['won_opportunities'] . ' closed deals')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart($this->getRevenueChartData()),

            Stat::make('Pipeline Value', '$' . number_format($metrics['total_pipeline_value'], 0))
                ->description('Across ' . $metrics['total_opportunities'] . ' opportunities')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Conversion Rate', $metrics['conversion_rate'] . '%')
                ->description($this->getConversionDescription($metrics['conversion_rate']))
                ->descriptionIcon($this->getConversionIcon($metrics['conversion_rate']))
                ->color($this->getConversionColor($metrics['conversion_rate'])),

            Stat::make('Average Probability', $metrics['average_probability'] . '%')
                ->description('Across active opportunities')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),
        ];
    }

    private function getFilterData(): array
    {
        return [
            'startDate' => $this->pageFilters['startDate'] ?? null,
            'endDate' => $this->pageFilters['endDate'] ?? null,
            'user_id' => $this->pageFilters['user_id'] ?? null,
            'organization_id' => $this->pageFilters['organization_id'] ?? null,
        ];
    }

    private function getRevenueChartData(): array
    {
        $reportingService = app(ReportingService::class);
        $trendData = $reportingService->getRevenueTrend($this->getFilterData());
        
        return array_slice(array_column($trendData, 'revenue'), -6); // Last 6 months
    }

    private function getConversionDescription(float $rate): string
    {
        if ($rate >= 30) {
            return 'Excellent conversion rate';
        } elseif ($rate >= 20) {
            return 'Good conversion rate';
        } elseif ($rate >= 10) {
            return 'Average conversion rate';
        } else {
            return 'Needs improvement';
        }
    }

    private function getConversionIcon(float $rate): string
    {
        return $rate >= 20 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    private function getConversionColor(float $rate): string
    {
        if ($rate >= 30) {
            return 'success';
        } elseif ($rate >= 20) {
            return 'info';
        } elseif ($rate >= 10) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
}