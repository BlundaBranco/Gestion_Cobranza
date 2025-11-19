<div x-data="{
    total_amount: {{ old('total_amount', 0) }},
    number_of_installments: {{ old('number_of_installments', 12) }},
    start_date: '{{ old('start_date', date('Y-m-d')) }}',
    installments: [],
    generated: false,

    generateInstallments() {
        const total = parseFloat(this.total_amount) || 0;
        const num = parseInt(this.number_of_installments) || 0;
        if (total <= 0 || num <= 0 || !this.start_date) {
            alert('Por favor, complete Monto Total, No. de Cuotas y Fecha de Inicio.');
            return;
        }

        this.installments = [];
        const baseAmount = Math.floor((total / num) * 100) / 100;
        let remainder = total - (baseAmount * num);

        const startDate = new Date(this.start_date + 'T00:00:00');

        for (let i = 0; i < num; i++) {
            let currentAmount = baseAmount;
            if (i === 0) {
                currentAmount = parseFloat((currentAmount + remainder).toFixed(2));
            }

            const dueDate = new Date(startDate);
            dueDate.setMonth(startDate.getMonth() + i);
            
            this.installments.push({
                due_date: dueDate.toISOString().split('T')[0],
                amount: currentAmount.toFixed(2)
            });
        }
        this.generated = true;
    }
}">

    <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900">Planes de Pago</h3>
            <p class="text-sm text-gray-600">Gestiona los planes de pago asociados a este lote.</p>
        </div>
    </div>

    <!-- Formulario de Creación -->
    <form method="POST" action="{{ route('lots.payment-plans.store', $lot) }}" class="border-t-2 border-gray-100 pt-6">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 items-end">
            <div>
                <x-input-label for="service_id" value="Servicio" />
                <select name="service_id" class="mt-1 block w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150" required>
                    @foreach(\App\Models\Service::all() as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="total_amount" value="Monto Total" />
                <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" class="mt-1 block w-full" x-model="total_amount" />
            </div>
            <div>
                <x-input-label for="number_of_installments" value="No. de Cuotas" />
                <x-text-input id="number_of_installments" name="number_of_installments" type="number" class="mt-1 block w-full" x-model="number_of_installments" />
            </div>
            <div>
                <x-input-label for="start_date" value="Fecha de Inicio" />
                <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" x-model="start_date" />
            </div>
            <div>
                <button type="button" @click="generateInstallments" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-700 text-white text-xs font-semibold uppercase rounded-xl hover:bg-gray-600 transition-all duration-150">
                    Generar Cuotas
                </button>
            </div>
        </div>

        <!-- Vista Previa Editable de Cuotas -->
        <div x-show="generated" class="mt-6 border-t-2 border-gray-100 pt-6" x-data="{ bulk_amount: '', applyBulk() { if (!isNaN(parseFloat(this.bulk_amount))) { const newAmount = parseFloat(this.bulk_amount).toFixed(2); this.installments.forEach(inst => inst.amount = newAmount); } } }">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-bold text-gray-900">Detalle de Cuotas (Editable)</h4>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.01" x-model="bulk_amount" placeholder="Monto para todos" class="w-32 border-2 border-gray-300 rounded-xl text-sm py-1 px-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="button" @click="applyBulk" class="text-sm text-blue-600 hover:underline font-semibold">Aplicar</button>
                </div>
            </div>
            <div class="max-h-72 overflow-y-auto border-2 border-gray-200 rounded-xl">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Vencimiento</th>
                            <th class="px-4 py-3 text-left">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="(installment, index) in installments" :key="index">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-gray-600 font-semibold" x-text="index + 1"></td>
                                <td class="px-4 py-4">
                                    <input type="hidden" :name="'due_dates[' + index + ']'" :value="installment.due_date">
                                    <span class="text-gray-800" x-text="new Date(installment.due_date + 'T00:00:00').toLocaleDateString()"></span>
                                </td>
                                <td class="px-4 py-4">
                                    <input type="number" step="0.01" :name="'amounts[' + index + ']'" x-model="installment.amount" class="w-full border-2 border-gray-300 rounded-xl text-sm py-1 px-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- BLOQUE DE ADVERTENCIA --}}
            <div x-show="generated" x-data="{
                    get sum() { return installments.reduce((sum, inst) => sum + parseFloat(inst.amount), 0); },
                    get total() { return parseFloat(total_amount); },
                    get difference() { return Math.abs(this.sum - this.total); }
                }">
                <div x-show="difference > 0.01" class="mt-3 p-3 bg-yellow-50 border-2 border-yellow-200 rounded-xl">
                    <p class="text-sm text-yellow-700 font-semibold">
                        ⚠️ Advertencia: La suma de las cuotas (<span x-text="`$${sum.toFixed(2)}`"></span>) no coincide con el Monto Total del plan (<span x-text="`$${total.toFixed(2)}`"></span>).
                    </p>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <x-primary-button class="inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar Plan de Pago
                </x-primary-button>
            </div>
        </div>
    </form>

    <!-- Lista de Planes de Pago Existentes -->
    <div class="mt-8 space-y-6">
        @forelse($lot->paymentPlans as $plan)
            @php
                $totalInstallments = $plan->installments->count();
                $paidInstallments = $plan->installments->filter(function($inst) {
                    $totalPaid = $inst->transactions->sum('pivot.amount_applied');
                    $totalDue = ($inst->amount ?? $inst->base_amount) + $inst->interest_amount;
                    return ($totalDue - $totalPaid) <= 0.005;
                })->count();
                $progressPercentage = $totalInstallments > 0 ? ($paidInstallments / $totalInstallments) * 100 : 0;
            @endphp
            
            <div class="rounded-xl border overflow-hidden">
                <div class="bg-gray-50 p-6 border-b">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900">{{ $plan->service->name }}</h4>
                            <p class="text-sm text-gray-600">{{ $totalInstallments }} cuotas totales</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Monto Total</p>
                            <p class="text-2xl font-bold text-gray-900">${{ number_format($plan->total_amount, 2) }}</p>
                        </div>
                    </div>

                    {{-- Barra de Progreso y Acciones del Plan --}}
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-semibold text-gray-700">Progreso del Plan</span>
                            {{-- Botón de Eliminar Plan --}}
                            <div>
                                @can('forceDelete', $plan)
                                    <form action="{{ route('payment-plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('ADMIN: ¿Seguro que deseas eliminar este plan? Esta acción es irreversible.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:underline">Eliminar Plan (Forzar)</button>
                                    </form>
                                @else
                                    @if(!$plan->installments()->whereHas('transactions')->exists())
                                        <form action="{{ route('payment-plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este plan de pago?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:underline">Eliminar Plan</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-green-500 h-2.5 rounded-full" style="width: {{ $progressPercentage }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600">
                            <span>{{ number_format($progressPercentage, 1) }}% completado</span>
                            <span>{{ $paidInstallments }} / {{ $totalInstallments }} cuotas pagadas</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white">
                    @include('lots._installments-table', ['plan' => $plan])
                </div>
            </div>
        @empty
            <div class="border-dashed border-2 rounded-lg p-8 text-center text-gray-500">
                <p>No hay planes de pago creados para este lote.</p>
            </div>
        @endforelse
    </div>
</div>