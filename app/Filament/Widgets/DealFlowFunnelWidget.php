<?php

namespace App\Filament\Widgets;

use App\Models\Opportunity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DealFlowFunnelWidget extends ChartWidget
{
    protected static ?string $heading = 'Deal Flow Funnel';
    
    protected static ?int $sort = 7;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '400px';
    
    public ?string $filter = '90';
    
    protected function getData(): array
    {
        $days = (int) $this->filter;
        $startDate = Carbon::now()->subDays($days);
        
        $stages = ['lead', 'prospect', 'proposal', 'negotiation', 'closed'];
        $stageLabels = [
            'lead' => 'Lead',
            'prospect' => 'Prospect',
            'proposal' => 'Proposal', 
            'negotiation' => 'Negotiation',
            'closed' => 'Closed/Won'
        ];
        
        $counts = [];
        $values = [];
        
        foreach ($stages as $stage) {
            if ($stage === 'closed') {
                // For closed, get won deals
                $stageOpportunities = Opportunity::where('status', 'won')
                    ->where('updated_at', '>=', $startDate)
                    ->get();
            } else {
                // For active stages, get current opportunities
                $stageOpportunities = Opportunity::where('stage', $stage)
                    ->where('isActive', true)
                    ->where('status', 'open')
                    ->get();
            }
            
            $counts[] = $stageOpportunities->count();
            $values[] = round($stageOpportunities->sum('value') / 1000, 1); // Convert to thousands
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Number of Opportunities',
                    'data' => $counts,
                    'backgroundColor' => [
                        'rgba(148, 163, 184, 0.8)',  // Slate
                        'rgba(251, 191, 36, 0.8)',   // Yellow
                        'rgba(59, 130, 246, 0.8)',   // Blue
                        'rgba(139, 92, 246, 0.8)',   // Purple
                        'rgba(34, 197, 94, 0.8)',    // Green
                    ],
                    'borderColor' => [
                        'rgba(148, 163, 184, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(139, 92, 246, 1)',
                        'rgba(34, 197, 94, 1)',
                    ],
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Total Value ($K)',
                    'data' => $values,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 3,
                    'type' => 'line',
                    'fill' => false,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => array_map(fn($stage) => $stageLabels[$stage], $stages),
        ];
    }
    
    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Opportunities'
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Value (Thousands $)'
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Pipeline Stage'
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
    
    protected function getFilters(): ?array
    {
        return [
            '30' => 'Last 30 days',
            '60' => 'Last 60 days',
            '90' => 'Last 90 days',
            '180' => 'Last 6 months',
            '365' => 'Last year',
        ];
    }
}