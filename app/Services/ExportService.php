<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    /**
     * Export data to CSV format
     */
    public function exportToCsv(Collection $data, array $headers, string $filename = null): string
    {
        $filename = $filename ?? 'export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $csvData = $this->generateCsvContent($data, $headers);
        
        Storage::disk('local')->put("exports/{$filename}", $csvData);
        
        return storage_path("app/exports/{$filename}");
    }

    /**
     * Export sales performance data to CSV
     */
    public function exportSalesPerformance(array $filters = []): string
    {
        $reportingService = app(ReportingService::class);
        
        // Get sales metrics
        $salesMetrics = $reportingService->getSalesMetrics($filters);
        $userPerformance = collect($reportingService->getUserPerformance($filters));
        $revenueData = collect($reportingService->getRevenueTrend($filters));
        
        // Create summary sheet data
        $summaryData = collect([
            ['Metric', 'Value'],
            ['Total Revenue', '$' . number_format($salesMetrics['total_revenue'], 2)],
            ['Total Opportunities', $salesMetrics['total_opportunities']],
            ['Won Opportunities', $salesMetrics['won_opportunities']],
            ['Lost Opportunities', $salesMetrics['lost_opportunities']],
            ['Conversion Rate', $salesMetrics['conversion_rate'] . '%'],
            ['Average Probability', $salesMetrics['average_probability'] . '%'],
            ['Pipeline Value', '$' . number_format($salesMetrics['total_pipeline_value'], 2)],
        ]);
        
        // Export summary
        $summaryFile = $this->exportToCsv(
            $summaryData,
            ['Metric', 'Value'],
            'sales_summary_' . now()->format('Y-m-d_H-i-s') . '.csv'
        );
        
        // Export user performance
        $userHeaders = ['Sales Rep', 'Total Opportunities', 'Won Opportunities', 'Total Revenue', 'Conversion Rate', 'Total Interactions'];
        $userData = $userPerformance->map(function ($user) {
            return [
                $user['name'],
                $user['total_opportunities'],
                $user['won_opportunities'],
                '$' . number_format($user['total_revenue'], 2),
                $user['conversion_rate'] . '%',
                $user['total_interactions'],
            ];
        });
        
        $userFile = $this->exportToCsv(
            $userData,
            $userHeaders,
            'user_performance_' . now()->format('Y-m-d_H-i-s') . '.csv'
        );
        
        // Export revenue trend
        $revenueHeaders = ['Month', 'Revenue', 'Deals Closed'];
        $revenueExportData = $revenueData->map(function ($item) {
            return [
                $item['month'],
                '$' . number_format($item['revenue'], 2),
                $item['deals_closed'],
            ];
        });
        
        $revenueFile = $this->exportToCsv(
            $revenueExportData,
            $revenueHeaders,
            'revenue_trend_' . now()->format('Y-m-d_H-i-s') . '.csv'
        );
        
        return $summaryFile; // Return main summary file
    }

    /**
     * Export opportunities data
     */
    public function exportOpportunities(array $filters = []): string
    {
        $query = \App\Models\Opportunity::with(['organization', 'contact', 'user']);
        
        // Apply filters
        if (!empty($filters['startDate'])) {
            $query->where('created_at', '>=', $filters['startDate']);
        }
        if (!empty($filters['endDate'])) {
            $query->where('created_at', '<=', $filters['endDate']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }
        
        $opportunities = $query->get();
        
        $headers = [
            'ID', 'Title', 'Organization', 'Contact', 'Assigned User', 
            'Stage', 'Status', 'Value', 'Probability', 'Expected Close Date', 
            'Created Date', 'Updated Date'
        ];
        
        $data = $opportunities->map(function ($opportunity) {
            return [
                $opportunity->id,
                $opportunity->title,
                $opportunity->organization->name ?? 'N/A',
                $opportunity->contact->full_name ?? 'N/A',
                $opportunity->user->name ?? 'N/A',
                $opportunity->stage,
                $opportunity->status,
                '$' . number_format($opportunity->value, 2),
                $opportunity->probability . '%',
                $opportunity->expected_close_date?->format('Y-m-d') ?? 'N/A',
                $opportunity->created_at->format('Y-m-d H:i:s'),
                $opportunity->updated_at->format('Y-m-d H:i:s'),
            ];
        });
        
        return $this->exportToCsv(
            $data,
            $headers,
            'opportunities_' . now()->format('Y-m-d_H-i-s') . '.csv'
        );
    }

    /**
     * Export organizations data
     */
    public function exportOrganizations(array $filters = []): string
    {
        $query = \App\Models\Organization::withCount(['contacts', 'opportunities', 'interactions']);
        
        // Apply filters
        if (!empty($filters['startDate'])) {
            $query->where('created_at', '>=', $filters['startDate']);
        }
        if (!empty($filters['endDate'])) {
            $query->where('created_at', '<=', $filters['endDate']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        
        $organizations = $query->get();
        
        $headers = [
            'ID', 'Name', 'Priority', 'Type', 'Status', 'Email', 'Phone', 
            'Address', 'Contacts Count', 'Opportunities Count', 'Interactions Count',
            'Estimated Revenue', 'Created Date'
        ];
        
        $data = $organizations->map(function ($org) {
            return [
                $org->id,
                $org->name,
                $org->priority,
                $org->type,
                $org->status,
                $org->email ?? 'N/A',
                $org->phone ?? 'N/A',
                $org->full_address ?? 'N/A',
                $org->contacts_count,
                $org->opportunities_count,
                $org->interactions_count,
                $org->estimated_revenue ? '$' . number_format($org->estimated_revenue, 2) : 'N/A',
                $org->created_at->format('Y-m-d H:i:s'),
            ];
        });
        
        return $this->exportToCsv(
            $data,
            $headers,
            'organizations_' . now()->format('Y-m-d_H-i-s') . '.csv'
        );
    }

    /**
     * Export interactions data
     */
    public function exportInteractions(array $filters = []): string
    {
        $query = \App\Models\Interaction::with(['organization', 'contact', 'user']);
        
        // Apply filters
        if (!empty($filters['startDate'])) {
            $query->where('interaction_date', '>=', $filters['startDate']);
        }
        if (!empty($filters['endDate'])) {
            $query->where('interaction_date', '<=', $filters['endDate']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        $interactions = $query->get();
        
        $headers = [
            'ID', 'Type', 'Organization', 'Contact', 'User', 'Date', 
            'Duration', 'Outcome', 'Priority', 'Follow Up Required', 'Notes'
        ];
        
        $data = $interactions->map(function ($interaction) {
            return [
                $interaction->id,
                $interaction->type,
                $interaction->organization->name ?? 'N/A',
                $interaction->contact->full_name ?? 'N/A',
                $interaction->user->name ?? 'N/A',
                $interaction->interaction_date->format('Y-m-d H:i:s'),
                $interaction->duration_minutes ? $interaction->duration_minutes . ' min' : 'N/A',
                $interaction->outcome ?? 'N/A',
                $interaction->priority ?? 'N/A',
                $interaction->follow_up_required ? 'Yes' : 'No',
                substr($interaction->notes ?? '', 0, 100),
            ];
        });
        
        return $this->exportToCsv(
            $data,
            $headers,
            'interactions_' . now()->format('Y-m-d_H-i-s') . '.csv'
        );
    }

    /**
     * Generate CSV content from collection
     */
    private function generateCsvContent(Collection $data, array $headers): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, is_array($row) ? $row : $row->toArray());
        }
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return $csvContent;
    }

    /**
     * Get export file URL for download
     */
    public function getDownloadUrl(string $filename): string
    {
        return Storage::disk('local')->url("exports/{$filename}");
    }

    /**
     * Clean up old export files
     */
    public function cleanupOldExports(int $daysOld = 7): void
    {
        $files = Storage::disk('local')->files('exports');
        $cutoffDate = now()->subDays($daysOld);
        
        foreach ($files as $file) {
            $fileTime = Storage::disk('local')->lastModified($file);
            if ($fileTime < $cutoffDate->timestamp) {
                Storage::disk('local')->delete($file);
            }
        }
    }
}