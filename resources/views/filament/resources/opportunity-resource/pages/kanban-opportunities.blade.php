<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Pipeline Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @foreach($this->stages as $stage)
                @php
                    $stats = $this->getStageStats($stage['id']);
                @endphp
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900">{{ $stage['title'] }}</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            {{ $stats['count'] }}
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-2xl font-semibold text-gray-900">${{ number_format($stats['total_value'], 0) }}</p>
                        <p class="text-sm text-gray-500">Weighted: ${{ number_format($stats['weighted_value'], 0) }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Kanban Board --}}
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Sales Pipeline</h2>
                <p class="text-sm text-gray-500">Drag opportunities between stages to update their status</p>
            </div>
            
            <div class="p-4">
                <div class="flex space-x-6 overflow-x-auto min-h-[600px]" id="kanban-board">
                    @foreach($this->stages as $stage)
                        <div class="flex-shrink-0 w-80" data-stage="{{ $stage['id'] }}">
                            {{-- Stage Header --}}
                            <div class="flex items-center justify-between mb-4 p-3 {{ $stage['color'] }} rounded-lg">
                                <h3 class="font-medium text-gray-900">{{ $stage['title'] }}</h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white text-gray-700">
                                    {{ $this->opportunities->get($stage['id'], collect())->count() }}
                                </span>
                            </div>
                            
                            {{-- Opportunities in this stage --}}
                            <div class="space-y-3 min-h-[500px] kanban-column" 
                                 data-stage="{{ $stage['id'] }}"
                                 ondrop="drop(event)" 
                                 ondragover="allowDrop(event)">
                                @foreach($this->opportunities->get($stage['id'], collect()) as $opportunity)
                                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm cursor-move hover:shadow-md transition-shadow kanban-card"
                                         draggable="true"
                                         data-opportunity-id="{{ $opportunity->id }}"
                                         ondragstart="drag(event)">
                                        
                                        {{-- Card Header --}}
                                        <div class="flex items-start justify-between mb-2">
                                            <h4 class="text-sm font-medium text-gray-900 truncate pr-2">
                                                {{ $opportunity->title }}
                                            </h4>
                                            <div class="flex space-x-1">
                                                @if($opportunity->priority === 'high')
                                                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                                @elseif($opportunity->priority === 'medium')
                                                    <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                                                @else
                                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        {{-- Organization --}}
                                        <p class="text-xs text-gray-600 mb-2">{{ $opportunity->organization->name }}</p>
                                        
                                        {{-- Value and Probability --}}
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-semibold text-green-600">
                                                {{ $opportunity->value_formatted }}
                                            </span>
                                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                {{ $opportunity->probability_formatted }}
                                            </span>
                                        </div>
                                        
                                        {{-- Expected Close Date --}}
                                        @if($opportunity->expectedCloseDate)
                                            <div class="flex items-center text-xs text-gray-500 mb-2">
                                                <x-heroicon-o-calendar class="w-3 h-3 mr-1"/>
                                                {{ $opportunity->expectedCloseDate->format('M j, Y') }}
                                            </div>
                                        @endif
                                        
                                        {{-- Assigned User --}}
                                        @if($opportunity->user)
                                            <div class="flex items-center text-xs text-gray-500 mb-3">
                                                <x-heroicon-o-user class="w-3 h-3 mr-1"/>
                                                {{ $opportunity->user->name }}
                                            </div>
                                        @endif
                                        
                                        {{-- Card Actions --}}
                                        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                            <span class="text-xs text-gray-400">
                                                {{ $opportunity->days_in_stage }}d in stage
                                            </span>
                                            <div class="flex space-x-1">
                                                <button onclick="editOpportunity('{{ $opportunity->id }}')" 
                                                        class="text-blue-600 hover:text-blue-800 text-xs">
                                                    Edit
                                                </button>
                                                <a href="{{ OpportunityResource::getUrl('edit', ['record' => $opportunity]) }}" 
                                                   class="text-gray-600 hover:text-gray-800 text-xs">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                
                                {{-- Add New Button --}}
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-gray-400 transition-colors">
                                    <a href="{{ OpportunityResource::getUrl('create') }}" 
                                       class="text-sm text-gray-600 hover:text-gray-800">
                                        <x-heroicon-o-plus class="w-5 h-5 mx-auto mb-1"/>
                                        Add Opportunity
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Drag and Drop JavaScript --}}
    <script>
        function allowDrop(ev) {
            ev.preventDefault();
        }

        function drag(ev) {
            ev.dataTransfer.setData("text", ev.target.dataset.opportunityId);
            ev.target.style.opacity = "0.5";
        }

        function drop(ev) {
            ev.preventDefault();
            
            // Reset opacity of all cards
            document.querySelectorAll('.kanban-card').forEach(card => {
                card.style.opacity = "1";
            });
            
            const opportunityId = ev.dataTransfer.getData("text");
            const newStage = ev.currentTarget.dataset.stage;
            
            // Call Livewire method to update opportunity stage
            @this.moveOpportunity(opportunityId, newStage);
        }

        // Add visual feedback for drag over
        document.querySelectorAll('.kanban-column').forEach(column => {
            column.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('bg-gray-50');
            });
            
            column.addEventListener('dragleave', function(e) {
                this.classList.remove('bg-gray-50');
            });
            
            column.addEventListener('drop', function(e) {
                this.classList.remove('bg-gray-50');
            });
        });

        // Reset card opacity when drag ends
        document.querySelectorAll('.kanban-card').forEach(card => {
            card.addEventListener('dragend', function(e) {
                this.style.opacity = "1";
            });
        });

        function editOpportunity(opportunityId) {
            // This would trigger a Filament action modal
            @this.mountAction('quickEdit', {
                opportunity: opportunityId
            });
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            @this.loadData();
        }, 30000);
    </script>

    {{-- Custom Styles --}}
    <style>
        .kanban-column {
            transition: background-color 0.2s ease;
        }
        
        .kanban-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .kanban-card:hover {
            transform: translateY(-2px);
        }
        
        .kanban-card:active {
            transform: scale(0.95);
        }
        
        /* Smooth scrolling for horizontal overflow */
        #kanban-board {
            scrollbar-width: thin;
        }
        
        #kanban-board::-webkit-scrollbar {
            height: 8px;
        }
        
        #kanban-board::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        #kanban-board::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        #kanban-board::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</x-filament-panels::page>
