<table class="w-full text-sm text-left text-gray-500">

    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th scope="col" class="px-6 py-3">Cliente</th>
            <th scope="col" class="px-6 py-3">Lote</th>
            <th scope="col" class="px-6 py-3"># Cuota</th>
            <th scope="col" class="px-6 py-3">Vencimiento</th>
            <th scope="col" class="px-6 py-3">Adeudo</th>
            <th scope="col" class="px-6 py-3 text-right">Acción</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($installments as $installment)
            @php

                // Asegurarse de que las relaciones necesarias estén cargadas para evitar N+1
                $installment->loadMissing('paymentPlan.lot.client', 'transactions');
                
                $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                $remaining = $totalDue - $totalPaid;

                // Obtener la moneda de la relación de la cuota
                $currency = $installment->paymentPlan->currency ?? 'MXN';

            @endphp
            @if($remaining > 0.005)
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        {{ $installment->paymentPlan->lot->client->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        {{ $installment->paymentPlan->lot->identifier ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        {{ $installment->installment_number }}
                    </td>
                    <td class="px-6 py-4 font-medium text-red-600">
                        {{ $installment->due_date->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-800">
                        {{ format_currency($remaining, $currency) }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-4">
                            @if($installment->paymentPlan->lot->client && $installment->paymentPlan->lot->client->phone)
                                <a href="{{ generate_whatsapp_message($installment, $remaining) }}" target="_blank" class="font-medium text-green-600 hover:underline">
                                    Notificar
                                </a>
                            @endif
                            <a href="{{ route('transactions.create', ['client_id' => $installment->paymentPlan->lot->client_id, 'installment_id' => $installment->id]) }}" class="font-medium text-blue-600 hover:underline">
                                Pagar
                            </a>
                        </div>
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    <p>No hay cuotas vencidas.</p>
                </td>
            </tr>
        @endforelse
    </tbody>

</table>