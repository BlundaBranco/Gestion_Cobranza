<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b-2 border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left font-bold">#</th>
                <th class="px-4 py-3 text-left font-bold">Vencimiento</th>
                <th class="px-4 py-3 text-left font-bold">Monto Base</th>
                <th class="px-4 py-3 text-left font-bold">Intereses</th>
                <th class="px-4 py-3 text-left font-bold">Total</th>
                <th class="px-4 py-3 text-left font-bold">Estado</th>
                <th class="px-4 py-3 text-right font-bold">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @foreach ($plan->installments->sortBy('installment_number') as $installment)
                @php
                    $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                    $totalDue = $installment->base_amount + $installment->interest_amount;
                    $remaining = $totalDue - $totalPaid;
                @endphp
                <tr class="dark:hover:bg-gray-700">
                    <td class="px-2 py-2">{{ $installment->installment_number }}</td>
                    <td class="px-2 py-2">{{ $installment->due_date->format('d/m/Y') }}</td>
                    <td class="px-2 py-2">${{ number_format($installment->base_amount, 2) }}</td>
                    <td class="px-2 py-2 text-yellow-400">${{ number_format($installment->interest_amount, 2) }}</td>
                    <td class="px-2 py-2">
                        ${{ number_format($totalDue, 2) }} 
                        @if($remaining > 0.005 && $remaining < $totalDue)
                            <span class="text-xs text-red-500">(Adeudo: ${{ number_format($remaining, 2) }})</span>
                        @endif
                    </td>
                    <td class="px-2 py-2">
                        @php
                            $statusClass = $remaining <= 0.005 ? 'bg-green-900 text-green-300' : ($installment->status == 'vencida' ? 'bg-red-900 text-red-300' : 'bg-yellow-900 text-yellow-300');
                        @endphp
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                            {{ $remaining <= 0.005 ? 'Pagada' : ucfirst($installment->status) }}
                        </span>
                    </td>
                    <td class="px-2 py-2 text-right space-x-4">
                        @if ($installment->interest_amount > 0)
                            <form action="{{ route('installments.condone', $installment) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro?');">
                                @csrf
                                <button type="submit" class="text-xs text-blue-400 hover:underline">Condonar</button>
                            </form>
                        @endif
                        @if($remaining > 0.005 && $lot->client && $lot->client->phone)
                            <a href="{{ generate_whatsapp_message($installment, $remaining) }}" target="_blank" class="text-xs text-green-400 hover:underline">Notificar</a>
                        @endif
                        @if($remaining > 0.005)
                            <a href="{{ route('transactions.create', ['client_id' => $lot->client_id, 'installment_id' => $installment->id]) }}" class="text-xs text-blue-400 hover:underline">Pagar</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>