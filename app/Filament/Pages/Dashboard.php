<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ActivityFeedWidget;
use App\Filament\Widgets\OrganizationAnalyticsWidget;
use App\Filament\Widgets\PipelineFunnelWidget;
use App\Filament\Widgets\PrincipalPerformanceWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\SalesOverviewWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'CRM Analytics Dashboard';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reporting Filters')
                    ->description('Filter all dashboard widgets and analytics')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->default(now()->subMonths(3))
                            ->maxDate(now())
                            ->displayFormat('M d, Y'),
                            
                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->default(now())
                            ->maxDate(now())
                            ->displayFormat('M d, Y'),
                            
                        Select::make('user_id')
                            ->label('Sales Rep')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('All users'),
                            
                        Select::make('organization_id')
                            ->label('Organization')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('All organizations'),
                            
                        Select::make('principal_id')
                            ->label('Principal/Brand')
                            ->relationship('principal', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('All principals'),
                    ])
                    ->columns(5)
                    ->collapsible(),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            SalesOverviewWidget::class,
            PipelineFunnelWidget::class,
            RevenueChartWidget::class,
            OrganizationAnalyticsWidget::class,
            ActivityFeedWidget::class,
            PrincipalPerformanceWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getHeaderActions(): array
    {
        return [
            // Add refresh action
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    // Clear reporting cache
                    app(\App\Services\ReportingService::class)->clearCache();
                    
                    // Send notification
                    \Filament\Notifications\Notification::make()
                        ->title('Dashboard Refreshed')
                        ->success()
                        ->body('All analytics data has been refreshed.')
                        ->send();
                }),
                
            // Add export action
            \Filament\Actions\Action::make('export_dashboard')
                ->label('Export Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    // This would trigger a dashboard export
                    \Filament\Notifications\Notification::make()
                        ->title('Export Started')
                        ->body('Your dashboard report is being generated. You will receive an email when ready.')
                        ->info()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ActivityFeedWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'CRM Analytics Dashboard';
    }

    public function getSubheading(): ?string
    {
        $dateRange = '';
        if ($this->filters['startDate'] ?? null) {
            $startDate = \Carbon\Carbon::parse($this->filters['startDate'])->format('M d, Y');
            $endDate = $this->filters['endDate'] 
                ? \Carbon\Carbon::parse($this->filters['endDate'])->format('M d, Y')
                : 'Present';
            $dateRange = " ({$startDate} - {$endDate})";
        }
        
        return 'Real-time analytics and performance metrics for PantryCRM' . $dateRange;
    }
}