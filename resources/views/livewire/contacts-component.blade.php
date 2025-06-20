<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Contacts</h2>
        <button class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
            + Add Contact
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
                'model' => 'organizationFilter',
                'placeholder' => 'All Organizations',
                'options' => $filters['organizations']
            ]
        ]"
        :search="[
            'model' => 'search',
            'placeholder' => 'Search by name, email, or position...'
        ]"
    />

    <x-table-wrapper :headers="['Priority', 'Contact', 'Organization', 'Position', 'Email', 'Last Contact', 'Actions']">
        @forelse($contacts as $contact)
            <div class="px-6 py-4 grid grid-cols-7 gap-4 items-center hover:bg-gray-50">
                <div>
                    <x-priority-dot :priority="$contact->priority" />
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ $contact->name }}</div>
                    <div class="text-xs text-gray-500">{{ $contact->phone }}</div>
                </div>
                <div class="text-sm text-gray-900">{{ $contact->organization->name }}</div>
                <div class="text-sm text-gray-900">{{ $contact->position }}</div>
                <div class="text-sm text-gray-900">{{ $contact->email }}</div>
                <div class="text-sm text-gray-900">{{ $contact->last_contact_formatted }}</div>
                <div>
                    <button class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600">
                        View
                    </button>
                </div>
            </div>
        @empty
            <div class="px-6 py-8 text-center text-gray-500">
                No contacts found.
            </div>
        @endforelse
    </x-table-wrapper>

    <div class="mt-4">
        {{ $contacts->links() }}
    </div>
</div>