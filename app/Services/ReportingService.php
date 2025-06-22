<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Interaction;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Principal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Get comprehensive sales metrics with caching
     */
    public function getSalesMetrics(array $filters = []): array
    {
        $cacheKey = 'sales_metrics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($filters) {
            $query = Opportunity::query();
            
            // Apply date filters
            if (!empty($filters['startDate'])) {
                $query->where('created_at', '>=', $filters['startDate']);
            }
            if (!empty($filters['endDate'])) {
                $query->where('created_at', '<=', $filters['endDate']);
            }
            
            // Get aggregated metrics
            $metrics = $query->selectRaw('
                COUNT(*) as total_opportunities,
                COUNT(CASE WHEN stage = "closed_won" THEN 1 END) as won_opportunities,
                COUNT(CASE WHEN stage = "closed_lost" THEN 1 END) as lost_opportunities,
                SUM(CASE WHEN stage = "closed_won" THEN value ELSE 0 END) as total_revenue,
                SUM(value) as total_pipeline_value,
                AVG(probability) as average_probability
            ')->first();
            
            // Calculate conversion rate
            $conversionRate = $metrics->total_opportunities > 0 
                ? round(($metrics->won_opportunities / $metrics->total_opportunities) * 100, 2)
                : 0;
            
            return [
                'total_opportunities' => $metrics->total_opportunities ?? 0,
                'won_opportunities' => $metrics->won_opportunities ?? 0,
                'lost_opportunities' => $metrics->lost_opportunities ?? 0,
                'total_revenue' => $metrics->total_revenue ?? 0,
                'total_pipeline_value' => $metrics->total_pipeline_value ?? 0,
                'average_probability' => round($metrics->average_probability ?? 0, 1),
                'conversion_rate' => $conversionRate,
            ];
        });
    }

    /**
     * Get pipeline funnel data for visualization
     */
    public function getPipelineFunnelData(array $filters = []): array
    {
        $cacheKey = 'pipeline_funnel_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($filters) {
            $query = Opportunity::query();
            
            // Apply filters
            $this->applyCommonFilters($query, $filters);
            
            $stages = $query->groupBy('stage')
                ->selectRaw('stage, COUNT(*) as count, SUM(value) as total_value')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->stage => [
                        'count' => $item->count,
                        'value' => $item->total_value,
                    ]];
                });
            
            // Define stage order for funnel
            $stageOrder = ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
            
            $funnelData = [];
            foreach ($stageOrder as $stage) {
                $funnelData[$stage] = $stages->get($stage, ['count' => 0, 'value' => 0]);
            }
            
            return $funnelData;
        });
    }

    /**
     * Get organization analytics
     */
    public function getOrganizationAnalytics(array $filters = []): array
    {
        $cacheKey = 'organization_analytics_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($filters) {
            $query = Organization::query();
            
            // Apply date filters
            if (!empty($filters['startDate']) || !empty($filters['endDate'])) {
                $query->when($filters['startDate'] ?? null, fn($q, $date) => $q->where('created_at', '>=', $date))
                      ->when($filters['endDate'] ?? null, fn($q, $date) => $q->where('created_at', '<=', $date));
            }
            
            return [
                'total_organizations' => $query->count(),
                'by_priority' => $query->groupBy('priority')
                    ->selectRaw('priority, COUNT(*) as count')
                    ->pluck('count', 'priority'),
                'by_type' => $query->groupBy('type')
                    ->selectRaw('type, COUNT(*) as count')
                    ->pluck('count', 'type'),
                'by_status' => $query->groupBy('status')
                    ->selectRaw('status, COUNT(*) as count')
                    ->pluck('count', 'status'),
                'recent_count' => $query->where('created_at', '>=', now()->subDays(30))->count(),
            ];
        });
    }

    /**
     * Get principal/brand performance data
     */
    public function getPrincipalPerformance(array $filters = []): array
    {
        $cacheKey = 'principal_performance_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, now()->addMinutes(20), function () use ($filters) {
            $principals = Principal::with(['productLines'])
                ->withCount('productLines')
                ->get()
                ->map(function ($principal) use ($filters) {
                    // Get opportunities related to this principal (through interactions or direct association)
                    $opportunityQuery = Opportunity::query();
                    
                    // Apply date filters
                    $this->applyCommonFilters($opportunityQuery, $filters);
                    
                    // For now, we'll calculate based on all opportunities
                    // In future, you might want to add principal relationships to opportunities
                    $totalOpportunities = $opportunityQuery->count();
                    $principalShare = $principal->productLines->count() > 0 ? 
                        ($principal->productLines->count() / Principal::sum(DB::raw('(SELECT COUNT(*) FROM product_lines WHERE principal_id = principals.id)'))) : 0;
                    
                    return [
                        'id' => $principal->id,
                        'name' => $principal->name,
                        'product_lines_count' => $principal->product_lines_count,
                        'estimated_opportunities' => round($totalOpportunities * $principalShare),
                        'contact_email' => $principal->email,
                        'website' => $principal->website,
                    ];
                });
            
            return $principals->toArray();
        });
    }

    /**
     * Get recent activity data
     */
    public function getRecentActivity(array $filters = [], int $limit = 10): array
    {
        $interactions = Interaction::with(['organization', 'contact', 'user'])
            ->when($filters['startDate'] ?? null, fn($q, $date) => $q->where('interaction_date', '>=', $date))
            ->when($filters['endDate'] ?? null, fn($q, $date) => $q->where('interaction_date', '<=', $date))
            ->orderBy('interaction_date', 'desc')
            ->limit($limit)
            ->get();
        
        return $interactions->map(function ($interaction) {
            return [
                'id' => $interaction->id,
                'type' => $interaction->type,
                'date' => $interaction->interaction_date,
                'organization' => $interaction->organization->name ?? 'Unknown',
                'contact' => $interaction->contact->full_name ?? 'Unknown',
                'user' => $interaction->user->name ?? 'System',
                'outcome' => $interaction->outcome,
                'notes' => substr($interaction->notes ?? '', 0, 100),
            ];
        })->toArray();
    }

    /**
     * Get revenue trend data for charts
     */
    public function getRevenueTrend(array $filters = []): array
    {
        $cacheKey = 'revenue_trend_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($filters) {
            $startDate = $filters['startDate'] ?? now()->subYear();
            $endDate = $filters['endDate'] ?? now();
            
            // Get monthly revenue data
            $revenueData = Opportunity::where('stage', 'closed_won')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->selectRaw('
                    DATE_FORMAT(updated_at, "%Y-%m") as month,
                    SUM(value) as revenue,
                    COUNT(*) as deals_closed
                ')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            
            // Fill in missing months with zero values
            $months = [];
            $current = Carbon::parse($startDate)->startOfMonth();
            $end = Carbon::parse($endDate)->endOfMonth();
            
            while ($current->lte($end)) {
                $monthKey = $current->format('Y-m');
                $existingData = $revenueData->firstWhere('month', $monthKey);
                
                $months[] = [
                    'month' => $current->format('M Y'),
                    'revenue' => $existingData->revenue ?? 0,
                    'deals_closed' => $existingData->deals_closed ?? 0,
                ];
                
                $current->addMonth();
            }
            
            return $months;
        });
    }

    /**
     * Get user performance metrics
     */
    public function getUserPerformance(array $filters = []): array
    {
        $cacheKey = 'user_performance_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($filters) {
            $userStats = DB::table('users')
                ->leftJoin('opportunities', 'users.id', '=', 'opportunities.user_id')
                ->leftJoin('interactions', 'users.id', '=', 'interactions.user_id')
                ->select([
                    'users.id',
                    'users.name',
                    DB::raw('COUNT(DISTINCT opportunities.id) as total_opportunities'),
                    DB::raw('COUNT(DISTINCT CASE WHEN opportunities.stage = "closed_won" THEN opportunities.id END) as won_opportunities'),
                    DB::raw('SUM(CASE WHEN opportunities.stage = "closed_won" THEN opportunities.value ELSE 0 END) as total_revenue'),
                    DB::raw('COUNT(DISTINCT interactions.id) as total_interactions'),
                ])
                ->when($filters['startDate'] ?? null, function ($query, $date) {
                    $query->where('opportunities.created_at', '>=', $date)
                          ->where('interactions.interaction_date', '>=', $date);
                })
                ->when($filters['endDate'] ?? null, function ($query, $date) {
                    $query->where('opportunities.created_at', '<=', $date)
                          ->where('interactions.interaction_date', '<=', $date);
                })
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_revenue')
                ->get();
            
            return $userStats->map(function ($stat) {
                $conversionRate = $stat->total_opportunities > 0 
                    ? round(($stat->won_opportunities / $stat->total_opportunities) * 100, 2)
                    : 0;
                
                return [
                    'id' => $stat->id,
                    'name' => $stat->name,
                    'total_opportunities' => $stat->total_opportunities,
                    'won_opportunities' => $stat->won_opportunities,
                    'total_revenue' => $stat->total_revenue,
                    'total_interactions' => $stat->total_interactions,
                    'conversion_rate' => $conversionRate,
                ];
            })->toArray();
        });
    }

    /**
     * Apply common filters to query
     */
    private function applyCommonFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['startDate'])) {
            $query->where('created_at', '>=', $filters['startDate']);
        }
        
        if (!empty($filters['endDate'])) {
            $query->where('created_at', '<=', $filters['endDate']);
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
    }

    /**
     * Clear all reporting caches
     */
    public function clearCache(): void
    {
        $cacheKeys = [
            'sales_metrics_*',
            'pipeline_funnel_*',
            'organization_analytics_*',
            'principal_performance_*',
            'revenue_trend_*',
            'user_performance_*',
        ];
        
        foreach ($cacheKeys as $pattern) {
            Cache::flush(); // For simplicity, flush all. In production, use more granular cache tags
        }
    }
}