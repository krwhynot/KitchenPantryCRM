<?php

namespace App\Livewire;

use App\Models\Interaction;
use App\Models\Organization;
use Livewire\Component;
use Livewire\WithPagination;

class InteractionsComponent extends Component
{
    use WithPagination;

    public $typeFilter = '';
    public $outcomeFilter = '';
    public $organizationFilter = '';
    public $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'outcomeFilter' => ['except' => ''],
        'organizationFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingOutcomeFilter()
    {
        $this->resetPage();
    }

    public function updatingOrganizationFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Interaction::with(['contact', 'organization']);

        if ($this->search) {
            $query->where('notes', 'like', '%' . $this->search . '%')
                  ->orWhereHas('contact', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('organization', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->outcomeFilter) {
            $query->where('outcome', $this->outcomeFilter);
        }

        if ($this->organizationFilter) {
            $query->where('organization_id', $this->organizationFilter);
        }

        $interactions = $query->orderBy('interaction_date', 'desc')->paginate(10);

        $filters = [
            'types' => cache()->remember('interaction_types', 300, function() {
                return Interaction::distinct()->pluck('type', 'type')->toArray();
            }),
            'outcomes' => cache()->remember('interaction_outcomes', 300, function() {
                return Interaction::distinct()->pluck('outcome', 'outcome')->toArray();
            }),
            'organizations' => cache()->remember('organization_list', 300, function() {
                return Organization::orderBy('name')->pluck('name', 'id')->toArray();
            }),
        ];

        return view('livewire.interactions-component', compact('interactions', 'filters'))
            ->layout('layouts.app');
    }
}