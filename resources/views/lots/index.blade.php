<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Gestión de Lotes</h2>
                    <p class="text-sm text-gray-600">Administra los lotes y sus pagos</p>
                </div>
            </div>
            <a href="{{ route('lots.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Lote
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Search -->
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 p-6">
                <form action="{{ route('lots.index') }}" method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-3">
                            <x-input-label for="search" value="Buscar" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Busca por 'Manzana 10 Lote 5', 'M 10', cliente o socio..." />
                        </div>
                        <div class="flex items-end gap-2">
                            <x-primary-button class="w-full justify-center">Buscar</x-primary-button>
                            <a href="{{ route('lots.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Lots Grid -->
            @if($lots->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($lots as $lot)
                        <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-blue-600 mb-1">{{ $lot->identifier }}</p>
                                        <h3 class="text-lg font-bold text-gray-900">{{ $lot->client->name ?? 'Sin Asignar' }}</h3>
                                        <p class="text-xs text-gray-500 mt-1">Socio: {{ $lot->owner->name ?? 'N/A' }}</p>
                                    </div>
                                    @php
                                        $statusClass = match($lot->status) {
                                            'disponible' => 'bg-green-100 text-green-800 border border-green-200',
                                            'vendido' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                            'liquidado' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                            default => 'bg-gray-100 text-gray-800 border border-gray-200',
                                        };
                                    @endphp
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                        {{ ucfirst($lot->status) }}
                                    </span>
                                </div>

                                @if($lot->total_debt > 0.01)
                                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-red-900">Deuda Total</span>
                                            <span class="text-lg font-bold text-red-600">
                                                {{-- Obtener la moneda de forma segura --}}
                                                @php
                                                    $currencyForLotCard = $lot->payment_plans_summary->isNotEmpty() ? $lot->payment_plans_summary->first()['currency'] : 'MXN';
                                                @endphp
                                                {{ format_currency($lot->total_debt, $currencyForLotCard) }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    @if($lot->payment_plans_summary->isNotEmpty())
                                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="text-sm font-semibold text-green-900">Sin deudas pendientes</span>
                                        </div>
                                    </div>
                                    @endif
                                @endif

                                <div class="pt-4 border-t border-gray-100 space-y-3">
                                    @foreach($lot->payment_plans_summary as $summary)
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600">{{ $summary['service_name'] }}</span>
                                            <span class="font-bold {{ $summary['debt'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                                {{ format_currency($summary['debt'], $summary['currency']) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end gap-2 bg-gray-50 px-6 py-4 border-t border-gray-100">
                                <a href="{{ route('lots.edit', $lot) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold rounded-lg transition-all duration-150 text-sm">
                                    Gestionar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @if ($lots->hasPages())
                    <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 p-6 mt-6">
                        {{ $lots->links() }}
                    </div>
                @endif
            @else
                <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 text-center py-16">
                    <p class="text-gray-600">No se encontraron lotes.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>