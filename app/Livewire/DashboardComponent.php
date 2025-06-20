<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Models\Contact;
use App\Models\Interaction;
use Livewire\Component;

class DashboardComponent extends Component
{
    public function render()
    {
        $stats = [
            'total_organizations' => Organization::count(),
            'total_contacts' => Contact::count(),
            'total_interactions' => Interaction::count(),
            'recent_interactions' => Interaction::with(['contact', 'organization'])
                ->latest('interaction_date')
                ->take(5)
                ->get(),
            'priority_breakdown' => [
                'A' => Organization::where('priority', 'A')->count(),
                'B' => Organization::where('priority', 'B')->count(),
                'C' => Organization::where('priority', 'C')->count(),
            ],
            'segment_breakdown' => Organization::selectRaw('segment, COUNT(*) as count')
                ->groupBy('segment')
                ->pluck('count', 'segment')
                ->toArray(),
        ];

        return view('livewire.dashboard-component', compact('stats'))
            ->layout('layouts.app');
    }
}