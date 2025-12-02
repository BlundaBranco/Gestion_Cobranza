<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Estado de Cuenta: {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Tarjeta de Información del Cliente -->
            <div class="bg-gradient-to-br from-indigo-50 to-white overflow-hidden shadow-lg sm:rounded-xl border border-indigo-100">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white text-xl font-bold shadow-md">
                                {{ strtoupper(substr($client->name, 0, 1)) }}
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">
                                Información del Cliente
                            </h3>
                        </div>
                        {{-- NUEVO BOTÓN EXPORTAR --}}
                        <a href="{{ route('clients.export', $client) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                            Exportar Excel
                        </a>
                        <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-indigo-200 rounded-lg text-sm font-medium text-indigo-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all duration-200 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Editar Cliente
                        </a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Nombre Completo</p>
                            <p class="text-base font-bold text-gray-900">{{ $client->name }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Teléfono</p>
                            <p class="text-base font-bold text-gray-900">{{ $client->phone ?? 'No registrado' }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Email</p>
                            <p class="text-base font-bold text-gray-900">{{ $client->email ?? 'No registrado' }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Dirección</p>
                            <p class="text-base font-bold text-gray-900">{{ $client->address ?? 'No registrada' }}</p>
                        </div>
                        @if($client->notes)
                        <div class="md:col-span-2 bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Notas</p>
                            <p class="text-base font-bold text-gray-900 whitespace-pre-wrap">{{ $client->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        <!-- Stats Cards (Desglose por Moneda) -->
        <div class="space-y-6">
            @php
                // Agrupar todos los planes del cliente por moneda
                $plansByCurrency = $client->lots->flatMap->paymentPlans->groupBy('currency');
            @endphp

            @forelse ($plansByCurrency as $currency => $plans)
                @php
                    // Calcular estadísticas para ESTA moneda específica
                    $data = [
                        'total_debt' => 0, 'debt_capital' => 0, 'debt_interest' => 0,
                        'total_paid' => 0, 'paid_capital' => 0, 'paid_interest' => 0,
                        'pending_installments' => 0
                    ];
                    foreach ($plans->flatMap->installments as $installment) {
                        $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                        $interest = $installment->interest_amount;
                        $base = $installment->amount ?? $installment->base_amount;
                        $totalDue = $base + $interest;
                        $remaining = $totalDue - $totalPaid;

                        if ($remaining > 0.01) {
                            $data['pending_installments']++;
                            $paidInterestOnDebt = min($totalPaid, $interest);
                            $paidCapitalOnDebt = $totalPaid - $paidInterestOnDebt;
                            $data['debt_interest'] += $interest - $paidInterestOnDebt;
                            $data['debt_capital'] += $base - $paidCapitalOnDebt;
                            $data['total_debt'] += $remaining;
                        }

                        $paidInterest = min($totalPaid, $interest);
                        $paidCapital = $totalPaid - $paidInterest;
                        $data['paid_interest'] += $paidInterest;
                        $data['paid_capital'] += $paidCapital;
                        $data['total_paid'] += $totalPaid;
                    }
                @endphp

                {{-- Contenedor para las estadísticas de una moneda --}}
                <div class="bg-white p-6 rounded-xl shadow-md border">
                    <h3 class="font-bold text-xl text-gray-800 mb-4">Resumen en {{ $currency }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                        <!-- Deuda en esta moneda -->
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <p class="text-sm font-medium text-red-700">Deuda Pendiente</p>
                            <p class="text-2xl font-bold text-red-600">{{ format_currency($data['total_debt'], $currency) }}</p>
                            <div class="mt-2 text-xs text-gray-600 space-y-1">
                                <div class="flex justify-between"><span>Capital:</span> <span class="font-semibold">${{ number_format($data['debt_capital'], 2) }}</span></div>
                                <div class="flex justify-between"><span>Intereses:</span> <span class="font-semibold">${{ number_format($data['debt_interest'], 2) }}</span></div>
                            </div>
                        </div>

                        <!-- Pagado en esta moneda -->
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <p class="text-sm font-medium text-green-700">Total Pagado</p>
                            <p class="text-2xl font-bold text-green-600">{{ format_currency($data['total_paid'], $currency) }}</p>
                            <div class="mt-2 text-xs text-gray-600 space-y-1">
                                <div class="flex justify-between"><span>A Capital:</span> <span class="font-semibold">${{ number_format($data['paid_capital'], 2) }}</span></div>
                                <div class="flex justify-between"><span>A Intereses:</span> <span class="font-semibold">${{ number_format($data['paid_interest'], 2) }}</span></div>
                            </div>
                        </div>

                        <!-- Info General -->
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-sm font-medium text-blue-700">Info General</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $data['pending_installments'] }}</p>
                            <p class="mt-2 text-xs text-gray-600">Cuotas con saldo pendiente en {{ $currency }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white p-6 rounded-xl shadow-md border text-center">
                    <p class="text-gray-500">Este cliente no tiene historial financiero.</p>
                </div>
            @endforelse
        </div>

            <!-- Tarjeta de Documentos del Cliente -->
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-gray-200">
                <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center text-white font-bold shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Documentos Adjuntos</h3>
                    </div>
                </div>
                <div class="p-6">
                    @if($client->documents->count() > 0)
                        <ul class="space-y-3">
                            @foreach($client->documents as $document)
                                <li class="flex justify-between items-center p-4 bg-gradient-to-r from-gray-50 to-white rounded-lg border border-gray-200 hover:border-purple-300 transition-all duration-200 shadow-sm hover:shadow-md">
                                    <a href="{{ $document->document_url }}" target="_blank" class="flex items-center gap-3 font-medium text-purple-600 hover:text-purple-800 transition-colors">
                                        <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <span>{{ $document->document_name }}</span>
                                    </a>
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este documento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded-md border border-red-200 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="bg-gray-50 rounded-lg p-8 text-center border-2 border-dashed border-gray-300">
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500 font-medium">No hay documentos adjuntos para este cliente.</p>
                        </div>
                    @endif
                </div>
            </div>


            <!-- Detalle de Lotes y Planes de Pago -->
            @if($client->lots->count() > 0)
                @foreach ($client->lots as $lot)
                    <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-gray-200">
                        <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-indigo-500 flex items-center justify-center text-white font-bold shadow-md">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Lote: {{ $lot->identifier }}</h3>
                            </div>
                        </div>
                        
                        <div class="p-6 text-gray-900">
                            @forelse ($lot->paymentPlans as $plan)
                                <div class="mt-4 first:mt-0">
                                    <div class="bg-gradient-to-r from-indigo-50 to-white rounded-lg p-4 mb-4 border border-indigo-100">
                                        <h4 class="font-bold text-lg text-gray-800">{{ $plan->service->name }} <span class="text-indigo-600">- Total: {{ format_currency($plan->total_amount, $plan->currency) }}</span></h4>
                                    </div>
                                    <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                                        <table class="w-full text-sm">
                                            <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-50 to-gray-100">
                                                <tr>
                                                    <th class="px-4 py-3 text-left font-semibold">#</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Vencimiento</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Monto Base</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Intereses</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Total</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Pagado</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Adeudo</th>
                                                    <th class="px-4 py-3 text-left font-semibold">Estado</th>
                                                    <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                @foreach ($plan->installments->sortBy('installment_number') as $installment)
                                                    @php
                                                        $totalDue = $installment->base_amount + $installment->interest_amount;
                                                        $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                                                        $remaining = $totalDue - $totalPaid;
                                                    @endphp
                                                    <tr class="hover:bg-indigo-50 transition-colors duration-150">
                                                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $installment->installment_number }}</td>
                                                        <td class="px-4 py-3 text-gray-700">{{ $installment->due_date->format('d/m/Y') }}</td>
                                                        <td class="px-4 py-3 font-medium text-gray-900">{{ format_currency($installment->base_amount, $plan->currency) }}</td>
                                                        <td class="px-4 py-3 font-medium text-yellow-600">{{ format_currency($installment->interest_amount, $plan->currency) }}</td>
                                                        <td class="px-4 py-3 font-bold text-gray-900">{{ format_currency($totalDue, $plan->currency) }}</td>
                                                        <td class="px-4 py-3 font-medium text-green-600">{{ format_currency($totalPaid, $plan->currency) }}</td>
                                                        <td class="px-4 py-3 font-bold {{ $remaining > 0.005 ? 'text-red-600' : 'text-green-600' }}">{{ format_currency($remaining, $plan->currency) }}</td>
                                                        <td class="px-4 py-3">
                                                            @php
                                                                $statusClass = $remaining <= 0.005 ? 'bg-green-100 text-green-800 border-green-200' : ($installment->status == 'vencida' ? 'bg-red-100 text-red-800 border-red-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200');
                                                            @endphp
                                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border {{ $statusClass }}">
                                                                {{ $remaining <= 0.005 ? 'Pagada' : ucfirst($installment->status) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 text-right">
                                                            <div class="flex items-center justify-end gap-2">
                                                                @if ($installment->interest_amount > 0)
                                                                    <form action="{{ route('installments.condone', $installment) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro?');">
                                                                        @csrf
                                                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-md border border-blue-200 transition-colors">
                                                                            Condonar
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                                @if($remaining > 0.005)
                                                                    <a href="{{ generate_whatsapp_message($installment, $remaining) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 rounded-md border border-green-200 transition-colors">
                                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                                        </svg>
                                                                        Notificar
                                                                    </a>
                                                                    <a href="{{ route('transactions.create', ['client_id' => $client->id, 'installment_id' => $installment->id]) }}" class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-md border border-indigo-200 transition-colors">
                                                                        Pagar
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-gray-50 rounded-lg p-8 text-center border-2 border-dashed border-gray-300">
                                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-gray-500 font-medium">Este lote no tiene planes de pago.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            @else
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl p-12 text-center border border-gray-200">
                    <div class="w-20 h-20 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <p class="text-lg text-gray-500 font-medium">Este cliente no tiene lotes asignados.</p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>