@props(['label', 'value', 'icon' => null, 'color' => 'blue', 'description' => null])

@php
$colors = [
    'blue'   => ['bg' => 'bg-blue-50', 'icon' => 'text-blue-600', 'ring' => 'ring-blue-100'],
    'green'  => ['bg' => 'bg-green-50', 'icon' => 'text-green-600', 'ring' => 'ring-green-100'],
    'amber'  => ['bg' => 'bg-amber-50', 'icon' => 'text-amber-600', 'ring' => 'ring-amber-100'],
    'red'    => ['bg' => 'bg-red-50', 'icon' => 'text-red-600', 'ring' => 'ring-red-100'],
    'purple' => ['bg' => 'bg-purple-50', 'icon' => 'text-purple-600', 'ring' => 'ring-purple-100'],
    'gray'   => ['bg' => 'bg-gray-50', 'icon' => 'text-gray-600', 'ring' => 'ring-gray-100'],
];
$c = $colors[$color] ?? $colors['blue'];
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4 shadow-sm">
    @if($icon)
        <div class="w-11 h-11 rounded-xl {{ $c['bg'] }} ring-1 {{ $c['ring'] }} flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 {{ $c['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $icon !!}
            </svg>
        </div>
    @endif
    <div class="min-w-0">
        <p class="text-sm text-gray-500 font-medium truncate">{{ $label }}</p>
        <p class="text-2xl font-bold text-gray-800 mt-0.5 leading-tight">{{ $value }}</p>
        @if($description)
            <p class="text-xs text-gray-400 mt-1">{{ $description }}</p>
        @endif
    </div>
</div>
