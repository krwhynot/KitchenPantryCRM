<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Interactions</h2>
        <button class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
            + Add Interaction
        </button>
    </div>

    <x-filter-bar 
        :filters="[
            [
                'model' => 'typeFilter',
                'placeholder' => 'All Types',
                'options' => $filters['types']
            ],
            [
                'model' => 'outcomeFilter',
                'placeholder' => 'All Outcomes',
                'options' => $filters['outcomes']
            ],
            [
                'model' => 'organizationFilter',
                'placeholder' => 'All Organizations',
                'options' => $filters['organizations']
            ]
        ]"
        :search="[
            'model' => 'search',
            'placeholder' => 'Search interactions...'
        ]"
    />

    <x-table-wrapper :headers="['Type', 'Date', 'Contact', 'Organization', 'Outcome', 'Notes', 'Actions']">
        @forelse($interactions as $interaction)
            <div class="px-6 py-4 grid grid-cols-7 gap-4 items-center hover:bg-gray-50">
                <div class="text-sm font-medium text-gray-900">{{ $interaction->type }}</div>
                <div class="text-sm text-gray-900">{{ $interaction->date_formatted }}</div>
                <div class="text-sm text-gray-900">{{ $interaction->contact->name }}</div>
                <div class="text-sm text-gray-900">{{ $interaction->organization->name }}</div>
                <div>
                    @php
                        $outcomeColor = match($interaction->outcome) {
                            'Positive', 'Closed Won' => 'bg-green-100 text-green-800',
                            'Negative', 'Closed Lost' => 'bg-red-100 text-red-800',
                            'Pending' => 'bg-yellow-100 text-yellow-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    @endphp
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $outcomeColor }}">
                        {{ $interaction->outcome }}
                    </span>
                </div>
                <div class="text-sm text-gray-600 truncate">{{ Str::limit($interaction->notes, 50) }}</div>
                <div>
                    <button class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">
                        View
                    </button>
                </div>
            </div>
        @empty
            <div class="px-6 py-8 text-center text-gray-500">
                No interactions found.
            </div>
        @endforelse
    </x-table-wrapper>

    <div class="mt-4">
        {{ $interactions->links() }}
    </div>
</div>