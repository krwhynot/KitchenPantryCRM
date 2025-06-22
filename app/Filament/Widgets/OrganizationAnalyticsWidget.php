<?php

namespace App\Filament\Widgets;

use App\Services\ReportingService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrganizationAnalyticsWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Organization Distribution';
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return 'Organization breakdown by priority, type, and status';
    }

    protected function getData(): array
    {
        $reportingService = app(ReportingService::class);
        $analytics = $reportingService->getOrganizationAnalytics($this->getFilterData());
        
        // Get the current filter to determine which data to show
        $filter = $this->filter ?? 'priority';
        
        switch ($filter) {
            case 'type':
                $data = $analytics['by_type'];
                $colors = [
                    'rgba(239, 68, 68, 0.8)',   // Red
                    'rgba(245, 158, 11, 0.8)',  // Amber
                    'rgba(34, 197, 94, 0.8)',   // Green
                    'rgba(59, 130, 246, 0.8)',  // Blue
                    'rgba(147, 51, 234, 0.8)',  // Purple
                ];
                break;
                
            case 'status':
                $data = $analytics['by_status'];
                $colors = [
                    'rgba(34, 197, 94, 0.8)',   // Green for active
                    'rgba(245, 158, 11, 0.8)',  // Amber for pending
                    'rgba(239, 68, 68, 0.8)',   // Red for inactive
                    'rgba(107, 114, 128, 0.8)', // Gray for other
                ];
                break;
                
            default: // priority
                $data = $analytics['by_priority'];
                $colors = [
                    'rgba(239, 68, 68, 0.8)',   // Red for A (high)
                    'rgba(245, 158, 11, 0.8)',  // Amber for B
                    'rgba(59, 130, 246, 0.8)',  // Blue for C
                    'rgba(107, 114, 128, 0.8)', // Gray for D (low)
                ];
                break;
        }
        
        $labels = $data->keys()->map(function ($key) {
            return ucfirst(str_replace('_', ' ', $key)) ?: 'Unknown';
        })->toArray();
        
        $values = $data->values()->toArray();
        
        return [
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => array_slice($colors, 0, count($values)),
                    'borderColor' => array_map(function ($color) {
                        return str_replace('0.8', '1', $color);
                    }, array_slice($colors, 0, count($values))),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            var label = context.label || "";
                            var value = context.raw || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = ((value / total) * 100).toFixed(1);
                            return label + ": " + value + " (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
            'layout' => [
                'padding' => 20,
            ],
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'priority' => 'By Priority',
            'type' => 'By Type',
            'status' => 'By Status',
        ];
    }

    public ?string $filter = 'priority';

    private function getFilterData(): array
    {
        return [
            'startDate' => $this->pageFilters['startDate'] ?? null,
            'endDate' => $this->pageFilters['endDate'] ?? null,
        ];
    }
}