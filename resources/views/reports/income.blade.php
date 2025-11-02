<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
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
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 p-6">
                <form method="GET" action="{{ route('reports.income') }}">
                    <div>
                        <x-input-label for="start_date" value="Fecha de Inicio" />
                        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="$startDate" />
                    </div>
                    <div>
                        <x-input-label for="end_date" value="Fecha de Fin" />
                        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="$endDate" />
                    </div>
                    {{-- NUEVO FILTRO --}}
                    <div>
                        <x-input-label for="owner_id" value="Socio" />
                        <select id="owner_id" name="owner_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Todos los Socios</option>
                            @foreach($owners as $owner)
                                <option value="{{ $owner->id }}" @selected(request('owner_id') == $owner->id)>
                                    {{ $owner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-primary-button class="w-full justify-center">Filtrar</x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Resultados -->
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 overflow-hidden">
                <div class="p-6 border-b-2 border-gray-200 bg-gradient-to-br from-gray-50 to-blue-50">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="p-1.5 bg-blue-100 rounded">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Resultados del Período</h3>
                            </div>
                            <p class="text-sm text-gray-600">
                                Del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="text-left md:text-right">
                            <p class="text-sm text-gray-600 mb-1">Total Ingresado</p>
                            <div class="flex items-center gap-2 md:justify-end">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <p class="text-3xl font-bold text-green-600">${{ number_format($totalIncome, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Folio</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Fecha de Pago</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Cliente</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($transactions as $transaction)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 font-semibold text-gray-900">
                                        <a href="{{ route('transactions.pdf', $transaction) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 hover:underline">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            {{ $transaction->folio_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $transaction->payment_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                                                <span class="text-white font-bold text-xs">
                                                    {{ substr($transaction->client->name, 0, 2) }}
                                                </span>
                                            </div>
                                            <span class="text-gray-900">{{ $transaction->client->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-green-600">${{ number_format($transaction->amount_paid, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-16">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <div class="p-4 bg-gray-100 rounded-full mb-4">
                                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                                </svg>
                                            </div>
                                            <p class="text-lg font-semibold text-gray-900 mb-2">No hay transacciones</p>
                                            <p class="text-gray-600">No se encontraron transacciones en este período</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>