<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Organizations</h2>
        <button class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
            + Add Organization
        </button>
    </div>

    <x-filter-bar 
        :filters="[
            [
                'model' => 'priorityFilter',
                'placeholder' => 'All Priorities',
                'options' => $filters['priorities']
            ],
            [
                'model' => 'segmentFilter', 
                'placeholder' => 'All Segments',
                'options' => $filters['segments']
            ]
        ]"
        :search="[
            'model' => 'search',
            'placeholder' => 'Search by name...'
        ]"
    />

    <x-table-wrapper :headers="['Priority', 'Organization', 'Segment', 'Type', 'Revenue', 'Last Contact', 'Actions']">
        @forelse($organizations as $organization)
            <div class="px-6 py-4 grid grid-cols-7 gap-4 items-center hover:bg-gray-50">
                <div>
                    <x-priority-dot :priority="$organization->priority" />
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ $organization->name }}</div>
                    <div class="text-xs text-gray-500">{{ $organization->address }}</div>
                </div>
                <div class="text-sm text-gray-900">{{ $organization->segment }}</div>
                <div class="text-sm text-gray-900">{{ $organization->type_label }}</div>
                <div class="text-sm text-gray-900">{{ $organization->estimated_revenue_formatted }}</div>
                <div class="text-sm text-gray-900">{{ $organization->last_contact_date_formatted }}</div>
                <div>
                    <button class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">
                        View
                    </button>
                </div>
            </div>
        @empty
            <div class="px-6 py-8 text-center text-gray-500">
                No organizations found.
            </div>
        @endforelse
    </x-table-wrapper>

    <div class="mt-4">
        {{ $organizations->links() }}
    </div>
</div>