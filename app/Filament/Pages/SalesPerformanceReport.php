<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\SalesOverviewWidget;
use App\Services\ReportingService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesPerformanceReport extends Page
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.sales-performance-report';
    protected static ?string $title = 'Sales Performance Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 1;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report Filters')
                    ->description('Customize your sales performance analysis')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->default(now()->subMonths(6))
                            ->maxDate(now()),
                            
                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->default(now())
                            ->maxDate(now()),
                            
                        Select::make('user_id')
                            ->label('Sales Representative')
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    // This would trigger PDF export
                    Notification::make()
                        ->title('PDF Export Started')
                        ->body('Your sales performance report is being generated as PDF.')
                        ->info()
                        ->send();
                }),
                
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(function () {
                    // This would trigger Excel export
                    Notification::make()
                        ->title('Excel Export Started')
                        ->body('Your sales performance report is being generated as Excel.')
                        ->info()
                        ->send();
                }),
                
            Action::make('schedule_report')
                ->label('Schedule Report')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->action(function () {
                    Notification::make()
                        ->title('Report Scheduling')
                        ->body('Report scheduling feature coming soon!')
                        ->warning()
                        ->send();
                }),
        ];
    }

    public function getReportData(): array
    {
        $reportingService = app(ReportingService::class);
        $filters = $this->getFilterData();
        
        return [
            'sales_metrics' => $reportingService->getSalesMetrics($filters),
            'revenue_trend' => $reportingService->getRevenueTrend($filters),
            'user_performance' => $reportingService->getUserPerformance($filters),
            'principal_performance' => $reportingService->getPrincipalPerformance($filters),
        ];
    }

    private function getFilterData(): array
    {
        return [
            'startDate' => $this->filters['startDate'] ?? null,
            'endDate' => $this->filters['endDate'] ?? null,
            'user_id' => $this->filters['user_id'] ?? null,
            'organization_id' => $this->filters['organization_id'] ?? null,
            'principal_id' => $this->filters['principal_id'] ?? null,
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
            RevenueChartWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Sales Performance Report';
    }

    public function getSubheading(): ?string
    {
        $filters = $this->getFilterData();
        $dateRange = '';
        
        if ($filters['startDate'] && $filters['endDate']) {
            $startDate = \Carbon\Carbon::parse($filters['startDate'])->format('M d, Y');
            $endDate = \Carbon\Carbon::parse($filters['endDate'])->format('M d, Y');
            $dateRange = " ({$startDate} - {$endDate})";
        }
        
        return 'Comprehensive sales analysis and performance metrics' . $dateRange;
    }
}