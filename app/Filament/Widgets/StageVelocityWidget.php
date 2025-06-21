<?php

namespace App\Filament\Widgets;

use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class StageVelocityWidget extends ChartWidget
{
    protected static ?string $heading = 'Stage Velocity Analysis';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';
    
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
            'closed' => 'Closed'
        ];
        
        $velocityData = [];
        
        foreach ($stages as $stage) {
            // Get stage history for transitions TO this stage
            $histories = OpportunityStageHistory::where('to_stage', $stage)
                ->where('created_at', '>=', $startDate)
                ->with('opportunity')
                ->get();
                
            if ($histories->isEmpty()) {
                $velocityData[] = 0;
                continue;
            }
            
            // Calculate average days in previous stage
            $totalDays = 0;
            $count = 0;
            
            foreach ($histories as $history) {
                $opportunity = $history->opportunity;
                if (!$opportunity) continue;
                
                // Find when they entered the previous stage
                $previousHistory = OpportunityStageHistory::where('opportunity_id', $opportunity->id)
                    ->where('to_stage', $history->from_stage)
                    ->where('created_at', '<', $history->created_at)
                    ->orderBy('created_at', 'desc')
                    ->first();
                    
                if ($previousHistory) {
                    $daysInStage = $history->created_at->diffInDays($previousHistory->created_at);
                } else {
                    // Use opportunity creation date as fallback
                    $daysInStage = $history->created_at->diffInDays($opportunity->created_at);
                }
                
                $totalDays += $daysInStage;
                $count++;
            }
            
            $averageDays = $count > 0 ? $totalDays / $count : 0;
            $velocityData[] = round($averageDays, 1);
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Average Days in Stage',
                    'data' => $velocityData,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',  // Blue
                        'rgba(251, 191, 36, 0.8)',  // Yellow
                        'rgba(139, 92, 246, 0.8)',  // Purple
                        'rgba(34, 197, 94, 0.8)',   // Green
                        'rgba(239, 68, 68, 0.8)',   // Red
                    ],
                    'borderColor' => [
                        'rgba(59, 130, 246, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(139, 92, 246, 1)',
                        'rgba(34, 197, 94, 1)',
                        'rgba(239, 68, 68, 1)',
                    ],
                    'borderWidth' => 2,
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
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Days'
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Pipeline Stage'
                    ],
                ],
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
        ];
    }
}