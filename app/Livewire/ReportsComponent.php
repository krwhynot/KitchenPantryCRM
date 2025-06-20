<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Models\Contact;
use App\Models\Interaction;
use Livewire\Component;
use Carbon\Carbon;

class ReportsComponent extends Component
{
    public function render()
    {
        $reports = [
            'interaction_summary' => [
                'this_month' => Interaction::whereMonth('interaction_date', now()->month)->count(),
                'last_month' => Interaction::whereMonth('interaction_date', now()->subMonth()->month)->count(),
                'by_type' => Interaction::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'by_outcome' => Interaction::selectRaw('outcome, COUNT(*) as count')
                    ->groupBy('outcome')
                    ->pluck('count', 'outcome')
                    ->toArray(),
            ],
            'organization_summary' => [
                'by_segment' => Organization::selectRaw('segment, COUNT(*) as count')
                    ->groupBy('segment')
                    ->pluck('count', 'segment')
                    ->toArray(),
                'by_priority' => Organization::selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority')
                    ->toArray(),
                'by_distributor' => Organization::selectRaw('distributor, COUNT(*) as count')
                    ->groupBy('distributor')
                    ->pluck('count', 'distributor')
                    ->toArray(),
            ],
            'contact_summary' => [
                'total' => Contact::count(),
                'by_priority' => Contact::selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority')
                    ->toArray(),
            ],
        ];

        return view('livewire.reports-component', compact('reports'))
            ->layout('layouts.app');
    }
}