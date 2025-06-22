<?php

namespace App\Livewire;

use App\Models\Organization;
use Livewire\Component;
use Livewire\WithPagination;

class OrganizationsComponent extends Component
{
    use WithPagination;

    public $priorityFilter = '';
    public $segmentFilter = '';
    public $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
        'segmentFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter()
    {
        $this->resetPage();
    }

    public function updatingSegmentFilter()
    {
        $this->resetPage();
    }


    public function render()
    {
        $query = Organization::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%');
        }

        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        if ($this->segmentFilter) {
            $query->where('segment', $this->segmentFilter);
        }


        $organizations = $query->with(['contacts', 'interactions'])->orderBy('name')->paginate(15);

        $filters = cache()->remember('organization_filters', 600, function () {
            $segments = Organization::query()
                ->whereNotNull('segment')
                ->distinct()
                ->pluck('segment')
                ->mapWithKeys(fn ($segment) => [$segment => $segment])
                ->sort()
                ->all();

            return [
                'priorities' => ['A' => 'Priority A', 'B' => 'Priority B', 'C' => 'Priority C'],
                'segments' => $segments,
            ];
        });

        return view('livewire.organizations-component', compact('organizations', 'filters'))
            ->layout('layouts.app');
    }
}