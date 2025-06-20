@props(['filters' => []])

<div class="mb-6 flex items-center space-x-4">
    @foreach($filters as $filter)
        <div>
            <select class="px-4 py-2 border border-gray-300 rounded-md bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    wire:model.live="{{ $filter['model'] }}">
                <option value="">{{ $filter['placeholder'] }}</option>
                @foreach($filter['options'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
    
    @if(isset($search))
        <div class="flex-1">
            <input type="search" 
                   placeholder="{{ $search['placeholder'] ?? 'Search...' }}" 
                   wire:model.live.debounce.300ms="{{ $search['model'] }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
    @endif
</div>