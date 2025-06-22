<div class="flex items-center justify-center space-x-2">
    @php
        $performance = $getRecord()['performance_grade'] ?? ['grade' => 'N/A', 'color' => 'gray', 'label' => 'Unknown'];
        $score = $getRecord()['performance_score'] ?? 0;
    @endphp
    
    <div class="flex items-center space-x-1">
        <span class="inline-flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full
            @switch($performance['color'])
                @case('success') bg-green-500 @break
                @case('info') bg-blue-500 @break  
                @case('warning') bg-yellow-500 @break
                @case('danger') bg-red-500 @break
                @default bg-gray-500
            @endswitch
        ">
            {{ $performance['grade'] }}
        </span>
        
        <div class="text-xs">
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $performance['label'] }}
            </div>
            <div class="text-gray-500 dark:text-gray-400">
                {{ $score }}/100
            </div>
        </div>
    </div>
    
    <!-- Performance Bar -->
    <div class="w-16 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
        <div class="h-2 rounded-full transition-all duration-300
            @switch($performance['color'])
                @case('success') bg-green-500 @break
                @case('info') bg-blue-500 @break  
                @case('warning') bg-yellow-500 @break
                @case('danger') bg-red-500 @break
                @default bg-gray-500
            @endswitch
        " style="width: {{ $score }}%"></div>
    </div>
</div>