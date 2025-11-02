<!-- index.blade.php - Versión mejorada con visualización de deuda -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Gestión de Lotes</h2>
                    <p class="text-sm text-gray-600">Administra los lotes y sus pagos</p>
                </div>
            </div>
            <a href="{{ route('lots.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
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
                            <x-input-label for="search" value="Buscar Lote o Cliente" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Identificador del lote, nombre del cliente..." />
                        </div>
                        <div class="flex items-end gap-2">
                            <x-primary-button class="w-full justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Buscar
                            </x-primary-button>
                            <a href="{{ route('lots.index') }}" class="inline-flex items-center px-4 py-2 bg-white border-2 border-gray-300 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition-all duration-150">
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
                        @php
                            $totalDebt = collect($lot->payment_plans_summary)->sum('debt');
                        @endphp
                        <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-blue-600 mb-1">{{ $lot->identifier }}</p>
                                        <h3 class="text-lg font-bold text-gray-900">{{ $lot->client->name ?? 'Sin Asignar' }}</h3>
                                    </div>
                                    @php
                                        $statusClass = match($lot->status) {
                                            'disponible' => 'bg-green-100 text-green-800 border-2 border-green-200',
                                            'vendido' => 'bg-yellow-100 text-yellow-800 border-2 border-yellow-200',
                                            'liquidado' => 'bg-blue-100 text-blue-800 border-2 border-blue-200',
                                            default => 'bg-gray-100 text-gray-800 border-2 border-gray-200',
                                        };
                                    @endphp
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                        {{ ucfirst($lot->status) }}
                                    </span>
                                </div>

                                <!-- Deuda Total Destacada -->
                                @if($totalDebt > 0)
                                    <div class="mb-4 p-3 bg-red-50 border-2 border-red-200 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold text-red-900">Deuda Total</span>
                                            <span class="text-lg font-bold text-red-600">${{ number_format($totalDebt, 2) }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="mb-4 p-3 bg-green-50 border-2 border-green-200 rounded-lg">
                                        <div class="flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-sm font-semibold text-green-900">Sin deudas pendientes</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="pt-4 border-t-2 border-gray-100 space-y-3">
                                    @foreach($lot->payment_plans_summary as $summary)
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-600">{{ $summary['service_name'] }}</span>
                                            <span class="font-bold {{ $summary['debt'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                                ${{ number_format($summary['debt'], 2) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end gap-2 bg-gradient-to-br from-gray-50 to-blue-50 px-6 py-4 border-t-2 border-gray-100">
                                <a href="{{ route('lots.edit', $lot) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold rounded-lg transition-all duration-150">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Gestionar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @if ($lots->hasPages())
                    <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 p-6">
                        {{ $lots->links() }}
                    </div>
                @endif
            @else
                <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 text-center py-16">
                    <div class="flex flex-col items-center justify-center">
                        <div class="p-4 bg-gray-100 rounded-full mb-4">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                        </div>
                        <p class="text-lg font-semibold text-gray-900 mb-2">No se encontraron lotes</p>
                        <p class="text-gray-600">Intenta ajustar los filtros de búsqueda o crea un nuevo lote</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>