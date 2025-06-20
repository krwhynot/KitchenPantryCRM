<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-500">Organizations</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_organizations'] }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-500">Contacts</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_contacts'] }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-500">Interactions</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['total_interactions'] }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-500">Priority A</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['priority_breakdown']['A'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Priority Breakdown</h3>
            <div class="space-y-3">
                @foreach($stats['priority_breakdown'] as $priority => $count)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-priority-dot :priority="$priority" />
                            <span class="ml-2 text-sm text-gray-600">Priority {{ $priority }}</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Interactions</h3>
            <div class="space-y-3">
                @forelse($stats['recent_interactions'] as $interaction)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $interaction->organization->name }}</div>
                            <div class="text-xs text-gray-500">{{ $interaction->type }} - {{ $interaction->contact->name }}</div>
                        </div>
                        <div class="text-xs text-gray-500">{{ $interaction->date_formatted }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No recent interactions</div>
                @endforelse
            </div>
        </div>
    </div>
</div>