@props(['color' => 'gray', 'size' => 'sm'])

@php
$colors = [
    'green'  => 'bg-green-100 text-green-800',
    'success'=> 'bg-green-100 text-green-800',
    'red'    => 'bg-red-100 text-red-800',
    'danger' => 'bg-red-100 text-red-800',
    'blue'   => 'bg-blue-100 text-blue-800',
    'info'   => 'bg-blue-100 text-blue-800',
    'amber'  => 'bg-amber-100 text-amber-800',
    'warning'=> 'bg-amber-100 text-amber-800',
    'gray'   => 'bg-gray-100 text-gray-700',
    'purple' => 'bg-purple-100 text-purple-800',
];
$cls = $colors[$color] ?? $colors['gray'];
$size = $size === 'xs' ? 'text-xs px-1.5 py-0.5' : 'text-xs px-2 py-1';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-medium rounded-full $cls $size"]) }}>
    {{ $slot }}
</span>
