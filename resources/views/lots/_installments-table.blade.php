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
                    
                    // Obtener última transacción para la fecha de pago y el folio
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
                                ${{ number_format($installment->amount ?? $installment->base_amount, 2) }}
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
                        ${{ number_format($installment->interest_amount, 2) }}
                    </td>

                    {{-- 5. Total (con Adeudo) --}}
                    <td class="px-4 py-3 font-bold text-gray-900">
                        ${{ number_format($totalDue, 2) }} 
                        @if($remaining > 0.005 && $remaining < $totalDue)
                            <div class="text-xs text-red-500 font-normal mt-1">Debe: ${{ number_format($remaining, 2) }}</div>
                        @endif
                    </td>

                    {{-- 6. Fecha de Pago (NUEVA COLUMNA) --}}
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
                            $statusClass = $remaining <= 0.005 ? 'bg-green-100 text-green-700 border border-green-200' : ($installment->status == 'vencida' ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-yellow-100 text-yellow-800 border border-yellow-200');
                            $statusLabel = $remaining <= 0.005 ? 'Pagada' : ucfirst($installment->status);
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>

                    {{-- 8. Acciones --}}
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            
                            {{-- BOTÓN VER FOLIO --}}
                            @if($lastTransaction)
                                <a href="{{ route('transactions.pdf', $lastTransaction) }}" target="_blank" class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" title="Ver Folio">
                                    <svg class="mr-1 h-3 w-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Folio
                                </a>
                            @endif

                            @if ($installment->interest_amount > 0)
                                <form action="{{ route('installments.condone', $installment) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas condonar los intereses?');">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">Condonar</button>
                                </form>
                            @endif

                            @if($remaining > 0.005)
                                @if($lot->client && $lot->client->phone)
                                    <a href="{{ generate_whatsapp_message($installment, $remaining) }}" target="_blank" class="flex items-center text-xs font-medium text-green-600 hover:text-green-800 transition-colors" title="Enviar WhatsApp">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.017-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        <span class="hidden sm:inline">WhatsApp</span>
                                    </a>
                                @endif
                                
                                <a href="{{ route('transactions.create', ['client_id' => $lot->client_id, 'installment_id' => $installment->id]) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-colors">
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