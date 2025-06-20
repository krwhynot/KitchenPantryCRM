<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Organization;
use Livewire\Component;
use Livewire\WithPagination;

class ContactsComponent extends Component
{
    use WithPagination;

    public $priorityFilter = '';
    public $organizationFilter = '';
    public $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
        'organizationFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter()
    {
        $this->resetPage();
    }

    public function updatingOrganizationFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Contact::with('organization');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('position', 'like', '%' . $this->search . '%');
        }

        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        if ($this->organizationFilter) {
            $query->where('organization_id', $this->organizationFilter);
        }

        $contacts = $query->orderBy('name')->paginate(10);

        $filters = [
            'priorities' => ['A' => 'Priority A', 'B' => 'Priority B', 'C' => 'Priority C'],
            'organizations' => cache()->remember('organization_list', 300, function() {
                return Organization::orderBy('name')->pluck('name', 'id')->toArray();
            }),
        ];

        return view('livewire.contacts-component', compact('contacts', 'filters'))
            ->layout('layouts.app');
    }
}