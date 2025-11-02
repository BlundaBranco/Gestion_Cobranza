<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
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
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 p-6">
                <form action="{{ route('transactions.index') }}" method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-3">
                            <x-input-label for="search" value="Buscar por Folio o Cliente" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Número de folio o nombre del cliente..." />
                        </div>
                        <div class="flex items-end gap-2">
                            <x-primary-button class="w-full justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Buscar
                            </x-primary-button>
                            <a href="{{ route('transactions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border-2 border-gray-300 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition-all duration-150">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Folio</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Cliente</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Fecha de Pago</th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Monto</th>
                                <th scope="col" class="px-6 py-4 text-right font-bold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($transactions as $transaction)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 font-semibold text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 bg-blue-100 rounded">
                                                <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            {{ $transaction->folio_number }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                                                <span class="text-white font-bold text-xs">
                                                    {{ substr($transaction->client->name, 0, 2) }}
                                                </span>
                                            </div>
                                            {{ $transaction->client->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        {{ $transaction->payment_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 font-bold text-green-600">
                                        ${{ number_format($transaction->amount_paid, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('transactions.pdf', $transaction) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold rounded-lg transition-all duration-150 text-xs">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Ver Folio
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-16">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <div class="p-4 bg-gray-100 rounded-full mb-4">
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
                    <div class="p-6 border-t-2 border-gray-200">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
            
             @if (session('new_transaction_id'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        window.open("{{ route('transactions.pdf', session('new_transaction_id')) }}", '_blank');
                    });
                </script>
            @endif
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