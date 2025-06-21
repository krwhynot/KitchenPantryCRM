<?php

namespace App\Filament\Widgets;

use App\Models\Opportunity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PipelineSummaryWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Current pipeline metrics
        $totalPipelineValue = Opportunity::where('isActive', true)
            ->where('status', 'open')
            ->sum('value');
            
        $weightedPipelineValue = Opportunity::where('isActive', true)
            ->where('status', 'open')
            ->get()
            ->sum(fn($opp) => $opp->weighted_value);
            
        $totalOpportunities = Opportunity::where('isActive', true)
            ->where('status', 'open')
            ->count();
            
        $averageDealSize = $totalOpportunities > 0 ? $totalPipelineValue / $totalOpportunities : 0;
        
        // Monthly performance metrics
        $monthlyClosedWon = Opportunity::where('status', 'won')
            ->whereBetween('updated_at', [$currentMonth, Carbon::now()])
            ->sum('value');
            
        $lastMonthClosedWon = Opportunity::where('status', 'won')
            ->whereBetween('updated_at', [$lastMonth, $currentMonth])
            ->sum('value');
            
        $monthlyGrowth = $lastMonthClosedWon > 0 
            ? (($monthlyClosedWon - $lastMonthClosedWon) / $lastMonthClosedWon) * 100 
            : 0;
            
        // Win rate calculation
        $totalClosedDeals = Opportunity::whereIn('status', ['won', 'lost'])
            ->whereBetween('updated_at', [$currentMonth->copy()->subMonths(3), Carbon::now()])
            ->count();
            
        $wonDeals = Opportunity::where('status', 'won')
            ->whereBetween('updated_at', [$currentMonth->copy()->subMonths(3), Carbon::now()])
            ->count();
            
        $winRate = $totalClosedDeals > 0 ? ($wonDeals / $totalClosedDeals) * 100 : 0;
        
        // Forecast accuracy (simplified)
        $forecastAccuracy = $this->calculateForecastAccuracy();

        return [
            Stat::make('Total Pipeline Value', '$' . number_format($totalPipelineValue, 0))
                ->description('Active opportunities')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('primary'),
                
            Stat::make('Weighted Pipeline', '$' . number_format($weightedPipelineValue, 0))
                ->description('Probability-adjusted forecast')
                ->descriptionIcon('heroicon-o-calculator')
                ->color('success'),
                
            Stat::make('Monthly Revenue', '$' . number_format($monthlyClosedWon, 0))
                ->description(($monthlyGrowth >= 0 ? '+' : '') . number_format($monthlyGrowth, 1) . '% from last month')
                ->descriptionIcon($monthlyGrowth >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($monthlyGrowth >= 0 ? 'success' : 'danger'),
                
            Stat::make('Win Rate', number_format($winRate, 1) . '%')
                ->description('Last 3 months')
                ->descriptionIcon('heroicon-o-trophy')
                ->color($winRate >= 20 ? 'success' : 'warning'),
                
            Stat::make('Average Deal Size', '$' . number_format($averageDealSize, 0))
                ->description($totalOpportunities . ' active opportunities')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info'),
                
            Stat::make('Forecast Accuracy', number_format($forecastAccuracy, 1) . '%')
                ->description('Prediction reliability')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($forecastAccuracy >= 80 ? 'success' : 'warning'),
        ];
    }
    
    private function calculateForecastAccuracy(): float
    {
        // Get opportunities that were forecasted to close last month
        $lastMonth = Carbon::now()->subMonth();
        $forecastedDeals = Opportunity::whereMonth('expectedCloseDate', $lastMonth->month)
            ->whereYear('expectedCloseDate', $lastMonth->year)
            ->get();
            
        if ($forecastedDeals->isEmpty()) {
            return 75.0; // Default accuracy
        }
        
        $totalForecasted = $forecastedDeals->sum('weighted_value');
        $actualClosed = $forecastedDeals->where('status', 'won')->sum('value');
        
        if ($totalForecasted == 0) {
            return 75.0;
        }
        
        return min(100, ($actualClosed / $totalForecasted) * 100);
    }
}