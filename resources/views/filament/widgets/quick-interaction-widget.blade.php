<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Quick Stats Row --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            @php
                $stats = $this->getTodaysStats();
                $recent = $this->getRecentInteractions();
            @endphp
            
            <div class="rounded-lg bg-primary-50 p-4 text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $stats['total'] }}</div>
                <div class="text-sm text-primary-700">Today's Interactions</div>
            </div>
            
            <div class="rounded-lg bg-success-50 p-4 text-center">
                <div class="text-2xl font-bold text-success-600">{{ $stats['calls'] }}</div>
                <div class="text-sm text-success-700">Calls Made</div>
            </div>
            
            <div class="rounded-lg bg-warning-50 p-4 text-center">
                <div class="text-2xl font-bold text-warning-600">{{ $stats['meetings'] }}</div>
                <div class="text-sm text-warning-700">Meetings Held</div>
            </div>
        </div>

        {{-- Main Quick Entry Form --}}
        <form wire:submit="create" class="space-y-4">
            {{ $this->form }}

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-4 border-t border-gray-200">
                {{-- Keyboard Shortcuts Info --}}
                <div class="text-sm text-gray-500">
                    <span class="font-medium">Speed Tips:</span>
                    <div class="mt-1 space-x-2">
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+Enter</kbd>
                        <span class="text-xs">Quick Save</span>
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Tab</kbd>
                        <span class="text-xs">Next Field</span>
                    </div>
                </div>
                
                {{-- Action Buttons --}}
                <div class="flex space-x-3">
                    {{ $this->createAction }}
                    {{ $this->createAndNewAction }}
                </div>
            </div>
        </form>

        {{-- Recent Interactions Preview --}}
        @if($recent->count() > 0)
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Interactions</h4>
                <div class="space-y-2">
                    @foreach($recent as $interaction)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($interaction->type === 'CALL') bg-blue-100 text-blue-800
                                    @elseif($interaction->type === 'EMAIL') bg-yellow-100 text-yellow-800
                                    @elseif($interaction->type === 'MEETING') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ $interaction->type_label }}
                                </span>
                                <span class="font-medium">{{ $interaction->subject }}</span>
                                <span class="text-gray-500">{{ $interaction->organization?->name }}</span>
                            </div>
                            <div class="text-gray-400">
                                {{ $interaction->interactionDate->diffForHumans() }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>

    {{-- Actions Modal --}}
    <x-filament-actions::modals />
</x-filament-widgets::widget>

{{-- Widget-specific Speed Optimizations --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus first field for immediate entry
    const firstInput = document.querySelector('#data\\.organization_id');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
    
    // Enhanced keyboard shortcuts for widget
    document.addEventListener('keydown', function(e) {
        // Ctrl+R for quick reset
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            @this.call('resetForm');
        }
        
        // Ctrl+D for duplicate last
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            @this.call('duplicateLastInteraction');
        }
    });
    
    // Performance timing feedback
    let startTime = performance.now();
    
    document.addEventListener('livewire:init', () => {
        Livewire.on('interaction-created', () => {
            const endTime = performance.now();
            const duration = ((endTime - startTime) / 1000).toFixed(1);
            
            if (duration < 30) {
                console.log(`🎉 Great! Interaction logged in ${duration}s (under 30s target)`);
            } else {
                console.log(`⚠️ Interaction took ${duration}s (target: under 30s)`);
            }
            
            startTime = performance.now(); // Reset for next interaction
        });
    });
});
</script>

{{-- Performance CSS --}}
<style>
/* Speed-optimized form styling */
.fi-wi-quick-interaction .fi-input:focus {
    border-color: rgb(59 130 246) !important;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
    transform: scale(1.01);
    transition: all 0.15s ease;
}

/* Quick visual feedback for selections */
.fi-wi-quick-interaction .fi-select-input[aria-expanded="true"] {
    background-color: rgb(239 246 255);
    border-color: rgb(59 130 246);
}

/* Highlight required fields for speed */
.fi-wi-quick-interaction .fi-input[required] {
    border-left: 3px solid rgb(34 197 94);
}

/* Button hover effects for immediate feedback */
.fi-wi-quick-interaction .fi-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.15s ease;
}

/* Loading state optimization */
.fi-wi-quick-interaction [wire\\:loading] {
    opacity: 0.7;
    pointer-events: none;
}

/* Recent interactions hover effect */
.fi-wi-quick-interaction .recent-interaction:hover {
    background-color: rgb(243 244 246);
    transform: translateX(4px);
    transition: all 0.15s ease;
}
</style>