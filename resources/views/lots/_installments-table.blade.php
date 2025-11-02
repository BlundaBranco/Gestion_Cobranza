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
        <tbody class="divide-y divide-gray-200 bg-white">
            @foreach ($plan->installments->sortBy('installment_number') as $installment)
                @php
                    $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                    $totalDue = $installment->base_amount + $installment->interest_amount;
                    $remaining = $totalDue - $totalPaid;
                @endphp
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $installment->installment_number }}</td>
                    <td class="px-4 py-3 text-gray-700 {{ $installment->status == 'vencida' && $remaining > 0.005 ? 'font-bold text-red-600' : '' }}">
                        {{ $installment->due_date->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 text-gray-900 font-semibold">${{ number_format($installment->base_amount, 2) }}</td>
                    <td class="px-4 py-3 text-yellow-700 font-semibold">${{ number_format($installment->interest_amount, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="font-bold text-gray-900">${{ number_format($totalDue, 2) }}</span>
                        @if($remaining > 0.005 && $remaining < $totalDue)
                            <span class="block text-xs text-red-600 font-semibold mt-1">(Adeudo: ${{ number_format($remaining, 2) }})</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $statusClass = $remaining <= 0.005 ? 'bg-green-100 text-green-800 border-2 border-green-200' : ($installment->status == 'vencida' ? 'bg-red-100 text-red-800 border-2 border-red-200' : 'bg-yellow-100 text-yellow-800 border-2 border-yellow-200');
                        @endphp
                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                            {{ $remaining <= 0.005 ? 'Pagada' : ucfirst($installment->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if ($installment->interest_amount > 0)
                                <form action="{{ route('installments.condone', $installment) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro que deseas condonar los intereses?');">
                                    @csrf
                                    <button type="submit" class="text-xs text-yellow-700 hover:underline">Condonar</button>
                                </form>
                            @endif
                            @if($remaining > 0.005)
                                <a href="{{ generate_whatsapp_message($installment, $remaining) }}" target="_blank" class="text-xs text-green-700 hover:underline">Notificar</a>
                                <a href="{{ route('transactions.create', ['client_id' => $lot->client_id, 'installment_id' => $installment->id]) }}" class="text-xs text-blue-700 hover:underline">Pagar</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>