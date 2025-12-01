<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Historial de Transacciones</h2>
                    <p class="text-sm text-gray-600">Consulta los pagos registrados</p>
                </div>
            </div>
            <a href="{{ route('transactions.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Registrar Pago
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Search -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900">Buscar Transacciones</h3>
                    </div>
                    <p class="text-sm text-gray-600">Filtra por folio, cliente o socio</p>
                </div>
                <div class="p-6">
                    <form action="{{ route('transactions.index') }}" method="GET">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div class="md:col-span-2">
                                <x-input-label for="search" value="Buscar por Folio o Cliente" />
                                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full focus:border-blue-500 focus:ring-blue-500" :value="request('search')" placeholder="Ingresa folio o nombre..." />
                            </div>
                            
                            <div>
                                <x-input-label for="owner_id" value="Socio" />
                                <select id="owner_id" name="owner_id" class="mt-1 block w-full border-gray-300 bg-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Todos los Socios</option>
                                    @foreach($owners as $owner)
                                        <option value="{{ $owner->id }}" @selected(request('owner_id') == $owner->id)>
                                            {{ $owner->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="flex items-end gap-2">
                                <x-primary-button class="flex-1 justify-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    Buscar
                                </x-primary-button>
                                <a href="{{ route('transactions.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition-colors duration-150">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Folio</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Cliente</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Socio</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Fecha de Pago</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Monto</th>
                                <th scope="col" class="px-6 py-4 text-right font-bold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($transactions as $transaction)
                                @php
                                    $ownerName = $transaction->installments->first()->paymentPlan->lot->owner->name ?? 'N/A';
                                @endphp
                                <tr class="hover:bg-blue-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 bg-blue-100 rounded-lg shadow-sm">
                                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            <span class="font-semibold text-gray-900">{{ $transaction->folio_number }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex-shrink-0 w-9 h-9 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-sm">
                                                <span class="text-white font-bold text-xs">
                                                    {{ strtoupper(substr($transaction->client->name, 0, 2)) }}
                                                </span>
                                            </div>
                                            <span class="text-gray-900 font-medium">{{ $transaction->client->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-semibold">
                                            {{ $ownerName }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 font-medium">
                                        {{ $transaction->payment_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-green-600">
                                        @php
                                            // Obtener la moneda de la primera cuota si existe
                                            $firstInstallment = $transaction->installments->first();
                                            $currency = $transaction->installments->first()->paymentPlan->currency ?? 'MXN';
                                        @endphp
                                        {{ format_currency($transaction->amount_paid, $currency) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Botón Ver Folio --}}
                                            <a href="{{ route('transactions.pdf', $transaction) }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold rounded-lg text-xs transition-colors duration-150 shadow-sm hover:shadow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Ver Folio
                                            </a>

                                            {{-- Botón Eliminar (Solo Admin) --}}
                                            @if(auth()->user()->role === 'admin')
                                                <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" onsubmit="return confirm('¿Eliminar este pago? Se revertirá el saldo de las cuotas.');" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 font-semibold rounded-lg text-xs transition-colors duration-150 shadow-sm hover:shadow">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <div class="p-4 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full mb-4 shadow-inner">
                                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                            </div>
                                            <p class="text-lg font-semibold text-gray-900 mb-2">No se encontraron transacciones</p>
                                            <p class="text-gray-600">Intenta ajustar los filtros de búsqueda</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($transactions->hasPages())
                    <div class="p-6 border-t border-gray-200 bg-gray-50">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if (session('new_transaction_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.open("{{ route('transactions.pdf', session('new_transaction_id')) }}", '_blank');
            });
        </script>
    @endif

</x-app-layout>