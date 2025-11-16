<form action="{{ route('installments.bulk-update', $plan) }}" method="POST" class="px-4 pt-4 pb-2 bg-gray-50 border-b">
    @csrf
    <div class="flex items-center justify-end gap-2">
        <label for="bulk_amount_{{ $plan->id }}" class="text-xs text-gray-600">Edición Rápida:</label>
        <input type="number" step="0.01" id="bulk_amount_{{ $plan->id }}" name="bulk_amount" placeholder="Nuevo monto" class="w-32 border-gray-300 rounded-md text-sm py-1 shadow-sm">
        <button type="submit" class="text-sm font-medium text-blue-600 hover:underline">Aplicar a Todas</button>
    </div>
</form>

<table class="w-full text-sm">
    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            <th class="px-2 py-3 text-left">#</th>
            <th class="px-2 py-3 text-left">Vencimiento</th>
            <th class="px-2 py-3 text-left">Monto Cuota</th>
            <th class="px-2 py-3 text-left">Intereses</th>
            <th class="px-2 py-3 text-left">Total</th>
            <th class="px-2 py-3 text-left">Estado</th>
            <th class="px-2 py-3 text-right">Acciones</th>
        </tr>
    </thead>

    <tbody class="divide-y divide-gray-200" x-data="{}">
        @foreach ($plan->installments->sortBy('installment_number') as $installment)
            @php
                $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                $remaining = $totalDue - $totalPaid;
            @endphp
            <tr class="hover:bg-gray-50" x-data="{ editing: false, amount: '{{ $installment->amount ?? $installment->base_amount }}' }">
                <td class="px-2 py-4 text-gray-700 font-medium">{{ $installment->installment_number }}</td>
                <td class="px-2 py-4 text-gray-600">{{ $installment->due_date->format('d/m/Y') }}</td>
                <td class="px-2 py-4 text-gray-800 font-semibold">
                    <div @dblclick="editing = true" x-show="!editing" class="cursor-pointer" title="Doble clic para editar">
                        ${{ number_format($installment->amount ?? $installment->base_amount, 2) }}
                    </div>
                    <div x-show="editing" x-cloak>
                        <form action="{{ route('installments.update', $installment) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="number" step="0.01" name="amount" x-model="amount" 
                                   @keydown.enter.prevent="$el.closest('form').submit()" 
                                   @blur="$el.closest('form').submit()"
                                   class="w-24 border-gray-300 rounded-md text-sm py-1 px-2 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                   x-init="$nextTick(() => $el.focus())">
                        </form>
                    </div>
                </td>
                <td class="px-2 py-4 text-yellow-600 font-semibold">${{ number_format($installment->interest_amount, 2) }}</td>
                <td class="px-2 py-4 text-gray-800 font-bold">
                    ${{ number_format($totalDue, 2) }}
                    @if($remaining > 0.005 && $remaining < $totalDue)
                        <span class="block text-xs text-red-600 font-normal">(Adeudo: ${{ number_format($remaining, 2) }})</span>
                    @endif
                </td>
                <td class="px-2 py-4">
                    @php
                        $statusClass = $remaining <= 0.005 ? 'bg-green-100 text-green-800' : ($installment->status == 'vencida' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                    @endphp
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                        {{ $remaining <= 0.005 ? 'Pagada' : ucfirst($installment->status) }}
                    </span>
                </td>
                <td class="px-2 py-4 text-right">
                    <div class="flex justify-end items-center space-x-4">
                        @if ($installment->interest_amount > 0)
                            <form action="{{ route('installments.condone', $installment) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro que deseas condonar los intereses?');">
                                @csrf
                                <button type="submit" class="text-xs text-blue-600 hover:underline">Condonar</button>
                            </form>
                        @endif
                        @if($remaining > 0.005 && $lot->client && $lot->client->phone)
                            <a href="{{ generate_whatsapp_message($installment, $remaining) }}" target="_blank" class="text-xs text-green-600 hover:underline">Notificar</a>
                        @endif
                        @if($remaining > 0.005)
                            <a href="{{ route('transactions.create', ['client_id' => $lot->client_id, 'installment_id' => $installment->id]) }}" class="text-xs text-blue-600 hover:underline">Pagar</a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>