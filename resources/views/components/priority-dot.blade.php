@props(['priority'])

@php
$colorClass = match($priority) {
    'A' => 'bg-green-500',
    'B' => 'bg-orange-500', 
    'C' => 'bg-red-500',
    default => 'bg-gray-500'
};
@endphp

<div class="flex items-center">
    <div class="w-2 h-2 rounded-full {{ $colorClass }} mr-2"></div>
    <span class="text-sm font-medium">{{ $priority }}</span>
</div>