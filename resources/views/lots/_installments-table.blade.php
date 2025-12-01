{{-- Formulario para edición masiva --}}
<form action="{{ route('installments.bulk-update', $plan) }}" method="POST" class="px-4 pt-4 pb-2 bg-white">
    @csrf
    <div class="flex items-center justify-end gap-2">
        <input type="number" step="0.01" name="bulk_amount" placeholder="Monto para todas" class="w-40 border-gray-300 rounded-md text-sm py-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <button type="submit" class="text-sm text-indigo-600 font-medium hover:underline">Aplicar a Todas</button>
    </div>
</form>

<div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Vencimiento</th>
                <th class="px-4 py-3">Monto Cuota</th>
                <th class="px-4 py-3">Intereses</th>
                <th class="px-4 py-3">Total</th>
                <th class="px-4 py-3">F. Pago</th> {{-- Columna 6 --}}
                <th class="px-4 py-3">Estado</th>  {{-- Columna 7 --}}
                <th class="px-4 py-3 text-right">Acciones</th> {{-- Columna 8 --}}
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 bg-white" x-data="{}">
            @foreach ($plan->installments->sortBy('installment_number') as $installment)
                @php
                    $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                    $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                    $remaining = $totalDue - $totalPaid;
                    $lastTransaction = $installment->transactions->sortByDesc('payment_date')->first();
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    
                    {{-- 1. Numero --}}
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $installment->installment_number }}</td>
                    
                    {{-- 2. Vencimiento (Editable) --}}
                    <td class="px-4 py-3" x-data="{ editingDate: false, date: '{{ $installment->due_date->format('Y-m-d') }}' }">
                        <div @dblclick="editingDate = true" x-show="!editingDate" class="cursor-pointer hover:text-indigo-600" title="Doble click para editar">
                            {{ $installment->due_date->format('d/m/Y') }}
                        </div>
                        <div x-show="editingDate" style="display: none;">
                            <form action="{{ route('installments.update', $installment) }}" method="POST">
                                @csrf @method('PUT')
                                <input type="date" name="due_date" x-model="date" 
                                    @keydown.enter.prevent="$el.closest('form').submit()" 
                                    @blur="$el.closest('form').submit()" 
                                    class="w-32 border-gray-300 rounded-md text-xs py-1 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </form>
                        </div>
                    </td>

                    {{-- 3. Monto Cuota (Editable) --}}
                    <td class="px-4 py-3 font-bold text-gray-800">
                        <div x-data="{ editing: false, amount: {{ $installment->amount ?? $installment->base_amount }} }">
                            <div @dblclick="editing = true" x-show="!editing" class="cursor-pointer hover:text-indigo-600" title="Doble click para editar">
                                {{ format_currency($installment->amount ?? $installment->base_amount, $plan->currency) }}
                            </div>
                            <div x-show="editing" style="display: none;">
                                <form action="{{ route('installments.update', $installment) }}" method="POST">
                                    @csrf @method('PUT')
                                    <input type="number" step="0.01" name="amount" x-model="amount" 
                                        @keydown.enter.prevent="$el.closest('form').submit()" 
                                        @blur="$el.closest('form').submit()" 
                                        class="w-24 border-gray-300 rounded-md text-xs py-1 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </form>
                            </div>
                        </div>
                    </td>

                    {{-- 4. Intereses --}}
                    <td class="px-4 py-3 text-orange-600 font-medium">
                        {{ format_currency($installment->interest_amount, $plan->currency) }}
                    </td>

                    {{-- 5. Total (con Adeudo) --}}
                    <td class="px-4 py-3 font-bold text-gray-900">
                        {{ format_currency($totalDue, $plan->currency) }} 
                        @if($remaining > 0.005 && $remaining < $totalDue)
                            <div class="text-xs text-red-500 font-normal mt-1">Debe: {{ format_currency($remaining, $plan->currency) }}</div>
                        @endif
                    </td>

                    {{-- 6. Fecha de Pago (Editable) --}}
                    <td class="px-4 py-3 text-gray-600">
                        @if($lastTransaction)
                            <div x-data="{ editing: false, date: '{{ $lastTransaction->payment_date->format('Y-m-d') }}' }">
                                <div @dblclick="editing = true" x-show="!editing" class="cursor-pointer hover:text-indigo-600 border-b border-dashed border-gray-300 pb-0.5" title="Doble click para editar fecha real">
                                    {{ $lastTransaction->payment_date->format('d/m/Y') }}
                                </div>
                                <div x-show="editing" style="display: none;">
                                    <form action="{{ route('transactions.update', $lastTransaction) }}" method="POST">
                                        @csrf @method('PUT')
                                        <input type="date" name="payment_date" x-model="date" 
                                            @keydown.enter.prevent="$el.closest('form').submit()" 
                                            @blur="$el.closest('form').submit()" 
                                            class="w-32 border-gray-300 rounded-md text-xs py-1 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </form>
                                </div>
                            </div>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>

                    {{-- 7. Estado --}}
                    <td class="px-4 py-3">
                        @php
                            $statusClass = $remaining <= 0.005 ? 'bg-green-100 text-green-700' : ($installment->status == 'vencida' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-800');
                            $statusLabel = $remaining <= 0.005 ? 'Pagada' : ucfirst($installment->status);
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>

                    {{-- 8. Acciones --}}
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            @if($lastTransaction)
                                <a href="{{ route('transactions.pdf', $lastTransaction) }}" target="_blank" class="text-xs font-medium text-gray-500 hover:text-indigo-600" title="Ver Folio">Folio</a>
                            @endif

                            @if ($installment->interest_amount > 0)
                                <form action="{{ route('installments.condone', $installment) }}" method="POST" onsubmit="return confirm('¿Seguro?');">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-blue-600 hover:underline">Condonar</button>
                                </form>
                            @endif

                            @if($remaining > 0.005)
                                @if($lot->client && $lot->client->phone)
                                    <a href="{{ generate_whatsapp_message($installment, $remaining) }}" target="_blank" class="text-xs font-medium text-green-600 hover:underline" title="Notificar">WhatsApp</a>
                                @endif
                                
                                <a href="{{ route('transactions.create', ['client_id' => $lot->client_id, 'installment_id' => $installment->id]) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">
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

{{-- Formulario para agregar nueva cuota manual --}}
<div class="bg-gray-50 p-4 border-t border-gray-200 rounded-b-xl" x-data="{ showAddForm: false }">
    <button type="button" @click="showAddForm = !showAddForm" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1" x-show="!showAddForm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Agregar Cuota / Enganche Extra
    </button>

    <form x-show="showAddForm" action="{{ route('payment-plans.installments.store', $plan) }}" method="POST" class="flex flex-wrap items-end gap-4" style="display: none;">
        @csrf
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">N° Cuota</label>
            <input type="number" name="installment_number" placeholder="0" class="w-20 border-gray-300 rounded-md text-sm py-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <p class="text-xs text-gray-400 mt-1">0 = Enganche</p>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Vencimiento</label>
            <input type="date" name="due_date" class="w-36 border-gray-300 rounded-md text-sm py-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required value="{{ date('Y-m-d') }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Monto</label>
            <input type="number" name="amount" step="0.01" placeholder="0.00" class="w-28 border-gray-300 rounded-md text-sm py-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-500 shadow-sm">
                Guardar
            </button>
            <button type="button" @click="showAddForm = false" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-md hover:bg-gray-50">
                Cancelar
            </button>
        </div>
    </form>
</div>