<?php

namespace App\Filament\Widgets;

use App\Services\ReportingService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PipelineFunnelWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Sales Pipeline Funnel';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        return 'Opportunity distribution across pipeline stages';
    }

    protected function getData(): array
    {
        $reportingService = app(ReportingService::class);
        $funnelData = $reportingService->getPipelineFunnelData($this->getFilterData());
        
        // Prepare data for funnel chart
        $labels = [];
        $counts = [];
        $values = [];
        
        $stageLabels = [
            'prospecting' => 'Prospecting',
            'qualification' => 'Qualification', 
            'proposal' => 'Proposal',
            'negotiation' => 'Negotiation',
            'closed_won' => 'Closed Won',
            'closed_lost' => 'Closed Lost',
        ];
        
        foreach ($funnelData as $stage => $data) {
            if (in_array($stage, ['closed_won', 'closed_lost'])) {
                continue; // Skip closed stages for funnel visualization
            }
            
            $labels[] = $stageLabels[$stage] ?? ucfirst($stage);
            $counts[] = $data['count'];
            $values[] = $data['value'];
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Opportunities',
                    'data' => $counts,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',   // Blue
                        'rgba(16, 185, 129, 0.8)',   // Green
                        'rgba(245, 158, 11, 0.8)',   // Yellow
                        'rgba(239, 68, 68, 0.8)',    // Red
                    ],
                    'borderColor' => [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(239, 68, 68, 1)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
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
            'responsive' => true,
            'maintainAspectRatio' => false,
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
}