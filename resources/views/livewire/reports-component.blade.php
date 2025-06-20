<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Reports</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Interaction Summary</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">This Month</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $reports['interaction_summary']['this_month'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Last Month</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $reports['interaction_summary']['last_month'] }}</span>
                </div>
                <div class="pt-4 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">By Type</h4>
                    @foreach($reports['interaction_summary']['by_type'] as $type => $count)
                        <div class="flex justify-between items-center py-1">
                            <span class="text-sm text-gray-600">{{ $type }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Organization Breakdown</h3>
            <div class="space-y-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-2">By Segment</h4>
                    @foreach($reports['organization_summary']['by_segment'] as $segment => $count)
                        <div class="flex justify-between items-center py-1">
                            <span class="text-sm text-gray-600">{{ $segment }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="pt-4 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">By Priority</h4>
                    @foreach($reports['organization_summary']['by_priority'] as $priority => $count)
                        <div class="flex justify-between items-center py-1">
                            <div class="flex items-center">
                                <x-priority-dot :priority="$priority" />
                                <span class="ml-2 text-sm text-gray-600">Priority {{ $priority }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Interaction Outcomes</h3>
            <div class="space-y-2">
                @foreach($reports['interaction_summary']['by_outcome'] as $outcome => $count)
                    <div class="flex justify-between items-center py-2">
                        <div>
                            @php
                                $outcomeColor = match($outcome) {
                                    'Positive', 'Closed Won' => 'bg-green-100 text-green-800',
                                    'Negative', 'Closed Lost' => 'bg-red-100 text-red-800',
                                    'Pending' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $outcomeColor }}">
                                {{ $outcome }}
                            </span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Distributor Summary</h3>
            <div class="space-y-2">
                @foreach($reports['organization_summary']['by_distributor'] as $distributor => $count)
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-gray-600">{{ $distributor }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>