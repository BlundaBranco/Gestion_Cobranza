<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-sm">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Reporte de Ingresos</h2>
                <p class="text-sm text-gray-600">Analiza los ingresos por período</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900">Filtros de Búsqueda</h3>
                    </div>
                </div>
                <form method="GET" action="{{ route('reports.income') }}" class="p-6">
                    {{-- Se ajustó a 6 columnas para acomodar el rango de folios --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
                        
                        {{-- Fechas --}}
                        <div>
                            <x-input-label for="start_date" value="Fecha Inicio" />
                            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full text-sm" :value="$startDate" />
                        </div>
                        <div>
                            <x-input-label for="end_date" value="Fecha Fin" />
                            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full text-sm" :value="$endDate" />
                        </div>

                        {{-- Rango de Folios (NUEVO) --}}
                        <div>
                            <x-input-label for="folio_from" value="Folio Desde" />
                            <x-text-input id="folio_from" name="folio_from" type="number" class="mt-1 block w-full text-sm" placeholder="Ej: 1" :value="request('folio_from')" />
                        </div>
                        <div>
                            <x-input-label for="folio_to" value="Folio Hasta" />
                            <x-text-input id="folio_to" name="folio_to" type="number" class="mt-1 block w-full text-sm" placeholder="Ej: 100" :value="request('folio_to')" />
                        </div>

                        {{-- Socio --}}
                        <div>
                            <x-input-label for="owner_id" value="Socio" />
                            <select id="owner_id" name="owner_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">Todos los Socios</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected(request('owner_id') == $owner->id)>
                                        {{ $owner->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Botón Filtrar --}}
                        <div>
                            <x-primary-button class="w-full justify-center h-[42px]">Filtrar</x-primary-button>
                        </div>
                    </div>

                    {{-- Botón Exportar --}}
                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                        <a href="{{ route('reports.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 transition shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Exportar Excel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Resultados -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gradient-to-br from-gray-50 to-blue-50">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-lg font-bold text-gray-900">Resultados del Período</h3>
                            </div>
                            <p class="text-sm text-gray-600">
                                Del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="text-left md:text-right">
                            <p class="text-sm text-gray-600 mb-1 font-medium">Total Ingresado</p>
                            <div class="flex items-center gap-2 md:justify-end">
                                <p class="text-3xl font-bold text-green-600">${{ number_format($totalIncome, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left font-bold">Folio</th>
                                <th class="px-6 py-4 text-left font-bold">Fecha de Pago</th>
                                <th class="px-6 py-4 text-left font-bold">Cliente</th>
                                <th class="px-6 py-4 text-left font-bold">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($transactions as $transaction)
                                <tr class="hover:bg-blue-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('transactions.pdf', $transaction) }}" target="_blank" class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-700 hover:underline font-semibold">
                                            {{ $transaction->folio_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 font-medium">{{ $transaction->payment_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4">
                                        <span class="text-gray-900 font-medium">{{ $transaction->client->name }}</span>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-green-600">
                                        @php
                                            $firstInstallment = $transaction->installments->first();
                                            $currency = $firstInstallment ? $firstInstallment->paymentPlan->currency : 'MXN';
                                        @endphp
                                        {{ format_currency($transaction->amount_paid, $currency) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <p class="text-lg font-semibold text-gray-900 mb-2">No hay transacciones</p>
                                        <p class="text-gray-600">No se encontraron transacciones en este período.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>R