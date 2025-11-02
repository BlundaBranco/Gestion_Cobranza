@props(['route', 'title', 'subtitle', 'icon', 'color'])

@php
    $colors = [
        'blue' => 'bg-blue-100 text-blue-600',
        'green' => 'bg-green-100 text-green-600',
        'yellow' => 'bg-yellow-100 text-yellow-600',
        'orange' => 'bg-orange-100 text-orange-600',
        'purple' => 'bg-purple-100 text-purple-600',
    ];
@endphp

<a href="{{ route($route) }}" class="flex items-center p-4 bg-gradient-to-r from-gray-50 to-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all duration-200 group">
    <span class="p-2.5 rounded-full mr-4 {{ $colors[$color] ?? $colors['blue'] }} flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
        @if ($icon === 'new-client')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /><path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM8 8a5 5 0 1 0 0 10A5 5 0 0 0 8 8Z" /></svg>
        @elseif ($icon === 'new-payment')
             <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H2Z" /><path d="M18 8H2a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1Zm-1 4a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1a1 1 0 0 1 1-1h14Z" /></svg>
        @elseif ($icon === 'new-lot')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
        @elseif ($icon === 'reports')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.5 2A1.5 1.5 0 0 0 1 3.5v13A1.5 1.5 0 0 0 2.5 18h15a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 17.5 2h-15Zm10 4a.75.75 0 0 1 .75.75v3c0 .414-.336.75-.75.75h-3a.75.75 0 0 1 0-1.5h2.25V6.75A.75.75 0 0 1 12.5 6ZM7.5 14a.75.75 0 0 1 .75-.75h4a.75.75 0 0 1 0 1.5h-4a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" /></svg>
        @endif
    </span>
    <div class="flex-1">
        <p class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">{{ $title }}</p>
        <p class="text-sm text-gray-600">{{ $subtitle }}</p>
    </div>
    <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
</a>