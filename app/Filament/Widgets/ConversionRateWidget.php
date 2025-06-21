<?php

namespace App\Filament\Widgets;

use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ConversionRateWidget extends ChartWidget
{
    protected static ?string $heading = 'Stage Conversion Rates';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '350px';
    
    public ?string $filter = '90';
    
    protected function getData(): array
    {
        $days = (int) $this->filter;
        $startDate = Carbon::now()->subDays($days);
        
        $stages = ['lead', 'prospect', 'proposal', 'negotiation'];
        $nextStages = ['prospect', 'proposal', 'negotiation', 'closed'];
        
        $stageLabels = [
            'lead' => 'Lead → Prospect',
            'prospect' => 'Prospect → Proposal', 
            'proposal' => 'Proposal → Negotiation',
            'negotiation' => 'Negotiation → Closed'
        ];
        
        $conversionRates = [];
        $benchmarks = [75, 65, 55, 45]; // Industry benchmark conversion rates
        
        foreach ($stages as $index => $fromStage) {
            $toStage = $nextStages[$index];
            
            // Get all opportunities that entered this stage in the time period
            $enteredStage = OpportunityStageHistory::where('to_stage', $fromStage)
                ->where('created_at', '>=', $startDate)
                ->distinct('opportunity_id')
                ->count('opportunity_id');
                
            if ($enteredStage == 0) {
                $conversionRates[] = 0;
                continue;
            }
            
            // Get opportunities that advanced to next stage
            $advancedToNext = OpportunityStageHistory::where('from_stage', $fromStage)
                ->where('to_stage', $toStage)
                ->where('created_at', '>=', $startDate)
                ->distinct('opportunity_id')
                ->count('opportunity_id');
                
            $conversionRate = ($advancedToNext / $enteredStage) * 100;
            $conversionRates[] = round($conversionRate, 1);
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Actual Conversion Rate (%)',
                    'data' => $conversionRates,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Industry Benchmark (%)',
                    'data' => $benchmarks,
                    'backgroundColor' => 'rgba(156, 163, 175, 0.5)',
                    'borderColor' => 'rgba(156, 163, 175, 1)',
                    'borderWidth' => 2,
                    'type' => 'line',
                    'fill' => false,
                ],
            ],
            'labels' => array_values($stageLabels),
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
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Conversion Rate (%)'
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Stage Transitions'
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