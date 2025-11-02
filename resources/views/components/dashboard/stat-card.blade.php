@props(['title', 'value', 'icon', 'color'])

@php
    $colors = [
        'blue' => 'bg-gradient-to-br from-blue-100 to-blue-200 text-blue-600 shadow-blue-200/50',
        'green' => 'bg-gradient-to-br from-green-100 to-green-200 text-green-600 shadow-green-200/50',
        'orange' => 'bg-gradient-to-br from-orange-100 to-orange-200 text-orange-600 shadow-orange-200/50',
    ];
@endphp

<div class="bg-white border-2 border-gray-200 p-7 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex items-center justify-between group">
    <div class="flex-1">
        <p class="text-sm font-semibold text-gray-600 mb-2 uppercase tracking-wide">{{ $title }}</p>
        <p class="text-4xl font-extrabold text-gray-900">{{ $value }}</p>
    </div>
    <div class="p-5 rounded-2xl {{ $colors[$color] ?? $colors['blue'] }} shadow-lg group-hover:scale-110 transition-transform duration-300">
        @if ($icon === 'clients')
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-4.663M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
        @elseif ($icon === 'lots')
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
        @elseif ($icon === 'transactions')
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
        @endif
    </div>
</div>