<?php

namespace App\Filament\Widgets;

use App\Models\Opportunity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ForecastAccuracyWidget extends ChartWidget
{
    protected static ?string $heading = 'Forecast vs Actual Revenue';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';
    
    public ?string $filter = '6';
    
    protected function getData(): array
    {
        $months = (int) $this->filter;
        
        $forecastData = [];
        $actualData = [];
        $labels = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $labels[] = $month->format('M Y');
            
            // Forecasted revenue (weighted pipeline at start of month)
            $forecasted = Opportunity::where('isActive', true)
                ->where('status', 'open')
                ->whereMonth('expectedCloseDate', $month->month)
                ->whereYear('expectedCloseDate', $month->year)
                ->get()
                ->sum(fn($opp) => $opp->weighted_value);
                
            // Actual revenue (deals won in that month)
            $actual = Opportunity::where('status', 'won')
                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                ->sum('value');
                
            $forecastData[] = round($forecasted / 1000, 1); // Convert to thousands
            $actualData[] = round($actual / 1000, 1);
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Forecasted Revenue ($K)',
                    'data' => $forecastData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'type' => 'line',
                ],
                [
                    'label' => 'Actual Revenue ($K)',
                    'data' => $actualData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
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
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": $" + context.formattedValue + "K";
                        }'
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue (Thousands $)'
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Month'
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
            '3' => 'Last 3 months',
            '6' => 'Last 6 months',
            '12' => 'Last 12 months',
        ];
    }
}