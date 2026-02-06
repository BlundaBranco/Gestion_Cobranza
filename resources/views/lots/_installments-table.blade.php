<div x-data="{ selected: [], allSelected: false }">
    
    <div class="bg-white border-b border-gray-100 px-4 py-3 flex flex-col md:flex-row justify-between items-center gap-4">
        {{-- 1. Barra de Acciones Masivas (Aparece al seleccionar) --}}
        <div x-show="selected.length > 0" class="flex-1 bg-indigo-50 p-2 rounded flex justify-between items-center w-full transition-all" style="display: none;">
            <span class="text-sm text-indigo-700 font-bold" x-text="selected.length + ' cuota(s) seleccionada(s)'"></span>
            <form action="{{ route('installments.bulk-condone') }}" method="POST">
                @csrf
                <template x-for="id in selected">
                    <input type="hidden" name="selected_installments[]" :value="id">
                </template>
                <button type="submit" class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700 font-semibold shadow-sm transition-colors" onclick="return confirm('¿Seguro que deseas condonar los intereses de TODAS las cuotas seleccionadas?');">
                    Condonar Intereses Seleccionados
                </button>
            </form>
        </div>

        {{-- 2. Formulario para Edición Masiva de Montos --}}
        <form action="{{ route('installments.bulk-update', $plan) }}" method="POST" class="flex items-center gap-2" x-show="selected.length === 0">
            @csrf
            <label class="text-xs text-gray-500 font-medium whitespace-nowrap">Edición Masiva:</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-400 text-xs">$</span>
                <input type="number" step="0.01" name="bulk_amount" placeholder="Monto" class="w-24 pl-5 border-gray-300 rounded-md text-sm py-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button type="submit" class="text-xs bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded hover:bg-gray-50 transition-colors font-medium shadow-sm">Aplicar a Todas</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 w-4">
                        <input type="checkbox" @change="allSelected = !allSelected; selected = allSelected ? [{{ $plan->installments->pluck('id')->join(',') }}] : []" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    </th>
                    <th class="px-4 py-3 font-semibold tracking-wider">#</th>
                    <th class="px-4 py-3 font-semibold tracking-wider">Vencimiento</th>
                    <th class="px-4 py-3 font-semibold tracking-wider">Monto Cuota</th>
                    <th class="px-4 py-3 font-semibold tracking-wider">Intereses</th>
                    <th class="px-4 py-3 font-semibold tracking-wider">Total</th>
                    <th class="px-4 py-3 font-semibold tracking-wider">F. Pago</th>
                    <th class="px-4 py-3 font-semibold tracking-wider">Estado</th>
                    <th class="px-4 py-3 text-right font-semibold tracking-wider">Acciones</th>
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
                    <tr class="hover:bg-gray-50 transition-colors group">
                        
                        {{-- Checkbox --}}
                        <td class="px-4 py-3">
                            <input type="checkbox" value="{{ $installment->id }}" x-model="selected" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </td>

                        {{-- 1. Numero --}}
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $installment->installment_number == 0 ? 'E' : $installment->installment_number }}
                        </td>
                        
                        {{-- 2. Vencimiento (Editable) --}}
                        <td class="px-4 py-3" x-data="{ editingDate: false, date: '{{ $installment->due_date->format('Y-m-d') }}' }">
                            <div @dblclick="editingDate = true" x-show="!editingDate" class="cursor-pointer group-hover:text-indigo-600 transition-colors border-b border-transparent hover:border-indigo-300 inline-block" title="Doble click para editar">
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
                                <div @dblclick="editing = true" x-show="!editing" class="cursor-pointer group-hover:text-indigo-600 transition-colors border-b border-transparent hover:border-indigo-300 inline-block" title="Doble click para editar">
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

                        {{-- 4. Intereses (Editable con prompt nativo) --}}
                        <td class="px-4 py-3 font-medium text-orange-600">
                            {{-- Formulario oculto para el envío --}}
                            <form id="interest-form-{{ $installment->id }}" action="{{ route('installments.update-interest', $installment) }}" method="POST" style="display: none;">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="interest_amount" id="input-interest-{{ $installment->id }}">
                            </form>

                            {{-- Botón que lanza prompt() nativo --}}
                            <button
                                type="button"
                                onclick="
                                    let currentInterest = '{{ $installment->interest_amount }}';
                                    let newAmount = prompt('Ingrese el nuevo monto de interés para la cuota {{ $installment->installment_number == 0 ? 'E' : $installment->installment_number }}:', currentInterest);

                                    if (newAmount !== null && newAmount.trim() !== '') {
                                        newAmount = newAmount.replace(',', '.');
                                        document.getElementById('input-interest-{{ $installment->id }}').value = newAmount;
                                        document.getElementById('interest-form-{{ $installment->id }}').submit();
                                    }
                                "
                                class="flex items-center gap-1.5 hover:text-orange-800 hover:bg-orange-50 px-2 py-1 rounded transition-colors cursor-pointer border-b border-dashed border-orange-300 hover:border-orange-500"
                                title="Clic para modificar interés manualmente (0 para condonar)"
                            >
                                <span>{{ format_currency($installment->interest_amount, $plan->currency) }}</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                        </td>

                        {{-- 5. Total (con Adeudo) --}}
                        <td class="px-4 py-3 font-bold text-gray-900">
                            {{ format_currency($totalDue, $plan->currency) }} 
                            @if($remaining > 0.005 && $remaining < $totalDue)
                                <div class="text-xs text-red-500 font-semibold mt-1 bg-red-50 px-1 rounded inline-block border border-red-100">
                                    Resta: {{ format_currency($remaining, $plan->currency) }}
                                </div>
                            @endif
                        </td>

                        {{-- 6. Fecha de Pago (Editable) --}}
                        <td class="px-4 py-3 text-gray-600">
                            @if($lastTransaction)
                                <div x-data="{ editing: false, date: '{{ $lastTransaction->payment_date->format('Y-m-d') }}' }">
                                    <div @dblclick="editing = true" x-show="!editing" class="cursor-pointer hover:text-indigo-600 border-b border-dashed border-gray-300 pb-0.5 inline-block" title="Doble click para editar fecha real">
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
                                <span class="text-gray-300">-</span>
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
                                    <a href="{{ route('transactions.pdf', $lastTransaction) }}" target="_blank" class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors" title="Ver Folio">
                                        Folio
                                    </a>
                                @endif

                                @if($remaining > 0.005)
                                    @if($lot->client && $lot->client->phone)
                                        <a href="{{ generate_whatsapp_message($installment, $remaining) }}" target="_blank" class="flex items-center text-xs font-medium text-green-600 hover:text-green-800 transition-colors px-1" title="Enviar WhatsApp">
                                            Notificar
                                        </a>
                                    @endif
                                    
                                    <a href="{{ route('transactions.create', ['client_id' => $lot->client_id, 'installment_id' => $installment->id, 'from_lot' => $lot->id]) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-colors ml-1">
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
                <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-500 shadow-sm transition-colors">
                    Guardar
                </button>
                <button type="button" @click="showAddForm = false" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-md hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>