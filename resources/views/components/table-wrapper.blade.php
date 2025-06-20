@props(['headers' => []])

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="bg-slate-700 px-6 py-3">
        <div class="grid grid-cols-{{ count($headers) }} gap-4 text-sm font-medium text-white">
            @foreach($headers as $header)
                <div>{{ $header }}</div>
            @endforeach
        </div>
    </div>
    <div class="divide-y divide-gray-200">
        {{ $slot }}
    </div>
</div>