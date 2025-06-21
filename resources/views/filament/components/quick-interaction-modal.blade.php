{{-- Quick Interaction Modal Component --}}
<div>
    {{-- Floating Action Button --}}
    <div class="fixed bottom-6 right-6 z-50">
        <button 
            type="button"
            wire:click="open"
            class="flex items-center justify-center w-14 h-14 bg-primary-600 hover:bg-primary-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
            title="Quick Interaction Entry (Ctrl+I)"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </div>

    {{-- Modal --}}
    <div 
        x-data="{ 
            show: @entangle('isOpen'),
            init() {
                // Global keyboard shortcut for Ctrl+I
                document.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'i' && !e.shiftKey) {
                        e.preventDefault();
                        this.show = true;
                        @this.open();
                    }
                });
            }
        }"
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div 
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            x-on:click="show = false; @this.close()"
        ></div>

        {{-- Modal Content --}}
        <div class="flex min-h-screen items-center justify-center p-4">
            <div 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="w-full max-w-2xl transform overflow-hidden rounded-lg bg-white shadow-xl transition-all"
            >
                {{-- Header --}}
                <div class="bg-primary-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-white">
                                ⚡ Quick Interaction Entry
                            </h3>
                            <p class="text-primary-100 text-sm">
                                Lightning-fast interaction logging - Target: 30 seconds
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            {{ $this->duplicateLastAction }}
                            <button 
                                type="button"
                                wire:click="close"
                                class="text-primary-200 hover:text-white transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Form --}}
                <form wire:submit="create" class="p-6 space-y-4">
                    {{ $this->form }}

                    {{-- Action Buttons --}}
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-500">
                            <span class="font-medium">Shortcuts:</span>
                            <kbd class="ml-1 px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+S</kbd> Save
                            <kbd class="ml-1 px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+Shift+S</kbd> Save & New
                            <kbd class="ml-1 px-2 py-1 bg-gray-100 rounded text-xs">Esc</kbd> Cancel
                        </div>
                        
                        <div class="flex space-x-3">
                            {{ $this->cancelAction }}
                            {{ $this->createAndNewAction }}
                            {{ $this->createAction }}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Actions Modal (for button modals) --}}
    <x-filament-actions::modals />
</div>

{{-- Global Styles for Quick Entry Optimization --}}
<style>
    /* Focus optimization for speed */
    .fi-input:focus,
    .fi-select-input:focus {
        border-color: rgb(59 130 246) !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
        transition: all 0.1s ease !important;
    }
    
    /* Quick visual feedback */
    .fi-btn:hover {
        transform: translateY(-1px);
        transition: transform 0.1s ease;
    }
    
    /* Speed-optimized animations */
    .fi-modal {
        animation-duration: 0.2s !important;
    }
    
    /* Highlight required fields */
    .fi-input[required] {
        border-left: 3px solid rgb(59 130 246);
    }
</style>

{{-- Quick Entry JavaScript Enhancements --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus first input when modal opens
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-modal', () => {
            setTimeout(() => {
                const firstInput = document.querySelector('.fi-modal input:not([type="hidden"]):first-of-type');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        });
    });
    
    // Enhanced keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Tab navigation optimization
        if (e.key === 'Tab') {
            const activeElement = document.activeElement;
            if (activeElement && activeElement.classList.contains('fi-input')) {
                // Smooth transition between fields
                setTimeout(() => {
                    const nextInput = document.activeElement;
                    if (nextInput && nextInput.type === 'text') {
                        nextInput.select();
                    }
                }, 10);
            }
        }
        
        // Enter to move to next field (except in textarea)
        if (e.key === 'Enter' && !e.shiftKey && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            const inputs = Array.from(document.querySelectorAll('.fi-input:not([type="hidden"])'));
            const currentIndex = inputs.indexOf(e.target);
            if (currentIndex > -1 && currentIndex < inputs.length - 1) {
                inputs[currentIndex + 1].focus();
            }
        }
    });
    
    // Performance optimization - preload common data
    setTimeout(() => {
        // Preload recent organizations and contacts for faster autocomplete
        fetch('/admin/api/recent-organizations')
            .then(response => response.json())
            .then(data => {
                // Cache in localStorage for faster subsequent loads
                localStorage.setItem('recent_organizations', JSON.stringify(data));
            })
            .catch(() => {
                // Silently fail if endpoint doesn't exist
            });
    }, 1000);
});
</script>