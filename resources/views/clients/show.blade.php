<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Estado de Cuenta: {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Header con Acciones -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-indigo-500 flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($client->name, 0, 1)) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $client->name }}</h1>
                            <p class="text-sm text-gray-600">{{ $client->email ?? $client->phone }}</p>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-4 sm:mt-0">
                        <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-400">
                            Editar
                        </a>
                        <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('¿Seguro?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-5 rounded-lg shadow-sm"><p class="text-sm text-gray-500">Lotes/Servicios</p><p class="text-2xl font-bold">{{ $stats['servicesCount'] }}</p></div>
                <div class="bg-white p-5 rounded-lg shadow-sm"><p class="text-sm text-gray-500">Deuda Total</p><p class="text-2xl font-bold text-red-500">${{ number_format($stats['totalDebt'], 2) }}</p></div>
                <div class="bg-white p-5 rounded-lg shadow-sm"><p class="text-sm text-gray-500">Cuotas Pendientes</p><p class="text-2xl font-bold">{{ $stats['pendingInstallmentsCount'] }}</p></div>
                <div class="bg-white p-5 rounded-lg shadow-sm"><p class="text-sm text-gray-500">Total Pagado</p><p class="text-2xl font-bold text-green-500">${{ number_format($stats['totalPaid'], 2) }}</p></div>
            </div>

            <!-- Detalle de Lotes y Planes de Pago -->
            @foreach ($client->lots as $lot)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-2">Lote: {{ $lot->identifier }}</h3>
                        
                        @forelse ($lot->paymentPlans as $plan)
                            <div class="mt-4">
                                <h4 class="font-semibold text-gray-700">{{ $plan->service->name }} - Total: ${{ number_format($plan->total_amount, 2) }}</h4>
                                <div class="overflow-x-auto mt-2">
                                    <table class="w-full text-sm">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left">#</th>
                                                <th class="px-4 py-2 text-left">Vencimiento</th>
                                                <th class="px-4 py-2 text-left">Monto Base</th>
                                                <th class="px-4 py-2 text-left">Intereses</th>
                                                <th class="px-4 py-2 text-left">Total</th>
                                                <th class="px-4 py-2 text-left">Pagado</th>
                                                <th class="px-4 py-2 text-left">Adeudo</th>
                                                <th class="px-4 py-2 text-left">Estado</th>
                                                <th class="px-4 py-2 text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($plan->installments->sortBy('installment_number') as $installment)
                                                @php
                                                    $totalDue = $installment->base_amount + $installment->interest_amount;
                                                    $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                                                    $remaining = $totalDue - $totalPaid;
                                                @endphp
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2">{{ $installment->installment_number }}</td>
                                                    <td class="px-4 py-2">{{ $installment->due_date->format('d/m/Y') }}</td>
                                                    <td class="px-4 py-2">${{ number_format($installment->base_amount, 2) }}</td>
                                                    <td class="px-4 py-2 text-yellow-600">${{ number_format($installment->interest_amount, 2) }}</td>
                                                    <td class="px-4 py-2">${{ number_format($totalDue, 2) }}</td>
                                                    <td class="px-4 py-2 text-green-600">${{ number_format($totalPaid, 2) }}</td>
                                                    <td class="px-4 py-2 font-bold {{ $remaining > 0.005 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($remaining, 2) }}</td>
                                                    <td class="px-4 py-2">
                                                        @php
                                                            $statusClass = $remaining <= 0.005 ? 'bg-green-100 text-green-800' : ($installment->status == 'vencida' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                                                        @endphp
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                                            {{ $remaining <= 0.005 ? 'Pagada' : ucfirst($installment->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2 text-right space-x-2">
                                                        @if ($installment->interest_amount > 0)
                                                            <form action="{{ route('installments.condone', $installment) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro?');">
                                                                @csrf
                                                                <button type="submit" class="text-xs text-blue-600 hover:underline">Condonar</button>
                                                            </form>
                                                        @endif
                                                        @if($remaining > 0.005)
                                                            <a href="{{ generate_whatsapp_message($installment, $remaining) }}" target="_blank" class="text-xs text-green-600 hover:underline">Notificar</a>
                                                            <a href="{{ route('transactions.create', ['client_id' => $client->id, 'installment_id' => $installment->id]) }}" class="text-xs text-blue-600 hover:underline">Pagar</a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <p class="mt-4 text-gray-500">Este lote no tiene planes de pago.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>