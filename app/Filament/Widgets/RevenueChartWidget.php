<?php

namespace App\Filament\Widgets;

use App\Services\ReportingService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RevenueChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Revenue Trend';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return 'Monthly revenue and deals closed over time';
    }

    protected function getData(): array
    {
        $reportingService = app(ReportingService::class);
        $trendData = $reportingService->getRevenueTrend($this->getFilterData());
        
        $labels = array_column($trendData, 'month');
        $revenueData = array_column($trendData, 'revenue');
        $dealsData = array_column($trendData, 'deals_closed');
        
        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Deals Closed',
                    'data' => $dealsData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            var label = context.dataset.label || "";
                            if (label === "Revenue ($)") {
                                return label + ": $" + context.raw.toLocaleString();
                            }
                            return label + ": " + context.raw;
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Month',
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue ($)',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) {
                            return "$" + value.toLocaleString();
                        }',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Deals Closed',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'last_6_months' => 'Last 6 months',
            'last_12_months' => 'Last 12 months',
            'this_year' => 'This year',
            'custom' => 'Custom range',
        ];
    }

    public ?string $filter = 'last_12_months';

    private function getFilterData(): array
    {
        $baseFilters = [
            'startDate' => $this->pageFilters['startDate'] ?? null,
            'endDate' => $this->pageFilters['endDate'] ?? null,
        ];
        
        // Override with chart-specific filter if not using custom range
        if ($this->filter !== 'custom') {
            switch ($this->filter) {
                case 'last_6_months':
                    $baseFilters['startDate'] = now()->subMonths(6)->startOfMonth();
                    $baseFilters['endDate'] = now()->endOfMonth();
                    break;
                case 'last_12_months':
                    $baseFilters['startDate'] = now()->subMonths(12)->startOfMonth();
                    $baseFilters['endDate'] = now()->endOfMonth();
                    break;
                case 'this_year':
                    $baseFilters['startDate'] = now()->startOfYear();
                    $baseFilters['endDate'] = now()->endOfYear();
                    break;
            }
        }
        
        return $baseFilters;
    }
}