<div x-data="{
    total_amount: {{ old('total_amount', 0) }},
    
    // Datos Enganche
    down_payment_total: 0,
    down_payment_count: 1,
    down_payment_date: '{{ date('Y-m-d') }}',

    // Datos Financiamiento
    regular_count: 12,
    regular_start_date: '', // Se calcula automáticamente

    // Datos del lote para la lógica
    block_number: '{{ $lot->block_number ?? '' }}',

    installments: [],
    generated: false,

    init() {
        this.calculateDefaultDates();
    },

    calculateDefaultDates() {
        // Extraer números de la manzana (ej: 'Manzana 14' -> 14)
        const match = this.block_number.match(/\d+/);
        const block = match ? parseInt(match[0]) : 0;

        // Regla: Manzana >= 10 paga el 30, sino el 10
        let day = 10;
        if (block >= 10) {
            day = 30;
        }

        // Calcular el mes siguiente con el día objetivo
        const today = new Date();
        // Mes + 1 (siguiente mes). El año se ajusta solo si pasamos de Dic a Ene.
        const targetDate = new Date(today.getFullYear(), today.getMonth() + 1, day);

        // Ajuste por si el mes siguiente no tiene día 30 (ej. Febrero)
        if (targetDate.getDate() !== day) {
            targetDate.setDate(0); // Último día del mes anterior
        }

        // Formatear a YYYY-MM-DD
        const year = targetDate.getFullYear();
        const month = String(targetDate.getMonth() + 1).padStart(2, '0');
        const d = String(targetDate.getDate()).padStart(2, '0');
        
        this.regular_start_date = `${year}-${month}-${d}`;
    },

    generateInstallments() {
        const total = parseFloat(this.total_amount) || 0;
        const downTotal = parseFloat(this.down_payment_total) || 0;
        const downCount = parseInt(this.down_payment_count) || 0;
        const regCount = parseInt(this.regular_count) || 0;

        if (total <= 0) { alert('Ingrese el Monto Total del Lote.'); return; }
        if (downTotal >= total) { alert('El enganche no puede cubrir todo el monto total.'); return; }

        this.installments = [];

        // --- 1. GENERAR CUOTAS DE ENGANCHE ---
        if (downTotal > 0 && downCount > 0) {
            const downBase = downTotal / downCount;
            const downRounded = Math.floor(downBase * 100) / 100;
            let downRemainder = downTotal - (downRounded * downCount);

            const dDate = new Date(this.down_payment_date + 'T00:00:00');

            for (let i = 0; i < downCount; i++) {
                let amount = downRounded;
                if (i === 0) amount += downRemainder;

                const currentDate = new Date(dDate);
                currentDate.setMonth(dDate.getMonth() + i);

                this.installments.push({
                    number: 0, 
                    type: 'Enganche ' + (i + 1) + '/' + downCount,
                    due_date: currentDate.toISOString().split('T')[0],
                    amount: amount.toFixed(2),
                    is_down_payment: true
                });
            }
        }

        // --- 2. GENERAR CUOTAS MENSUALES ---
        const regTotal = total - downTotal;
        if (regTotal > 0 && regCount > 0) {
            const regBase = Math.floor((regTotal / regCount) * 100) / 100;
            let regRemainder = regTotal - (regBase * regCount);

            // Parsear la fecha manual para asegurar exactitud
            const parts = this.regular_start_date.split('-');
            const startYear = parseInt(parts[0]);
            const startMonth = parseInt(parts[1]) - 1; // 0-11 en JS
            const startDay = parseInt(parts[2]);

            for (let i = 0; i < regCount; i++) {
                let amount = regBase;
                if (i === 0) amount += regRemainder;

                // Calcular fecha sumando meses
                const currentDate = new Date(startYear, startMonth + i, startDay);

                // Corrección de desbordamiento de mes (ej: 30 de Feb -> 2 de Marzo)
                // Si el día resultante no es el día solicitado, retrocedemos al último día del mes correcto.
                if (currentDate.getDate() !== startDay) {
                    currentDate.setDate(0); 
                }

                this.installments.push({
                    number: i + 1,
                    type: 'Mensualidad',
                    due_date: currentDate.toISOString().split('T')[0],
                    amount: amount.toFixed(2),
                    is_down_payment: false
                });
            }
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
    <form method="POST" action="{{ route('lots.payment-plans.store', $lot) }}">
        @csrf
        
        <div class="mt-6 border-t border-gray-200 pt-6">
            <!-- Configuración Global -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6"> 
                <div>
                    <x-input-label for="service_id" value="Tipo de Servicio" />
                    <select name="service_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        @foreach(\App\Models\Service::all() as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- CAMPO DE MONEDA AÑADIDO --}}
                <div>
                    <x-input-label for="currency" value="Moneda" />
                    <select name="currency" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="MXN">Pesos (MXN)</option>
                        <option value="USD">Dólares (USD)</option>
                    </select>
                </div>


                <div>
                    <x-input-label for="total_amount" value="Precio Total del Lote ($)" />
                    <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" class="mt-1 block w-full text-lg font-bold text-gray-900" x-model="total_amount" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Columna Izquierda: ENGANCHE -->
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <h4 class="font-bold text-yellow-800 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        1. Configuración de Enganche
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <x-input-label value="Monto Total Enganche ($)" />
                            <x-text-input type="number" step="0.01" class="block w-full border-yellow-300 focus:border-yellow-500 focus:ring-yellow-500" x-model="down_payment_total" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <x-input-label value="Cant. Cuotas" />
                                <x-text-input type="number" class="block w-full border-yellow-300 focus:border-yellow-500 focus:ring-yellow-500" x-model="down_payment_count" />
                            </div>
                            <div>
                                <x-input-label value="Fecha 1er Pago" />
                                <x-text-input type="date" class="block w-full border-yellow-300 focus:border-yellow-500 focus:ring-yellow-500" x-model="down_payment_date" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: MENSUALIDADES -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h4 class="font-bold text-blue-800 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        2. Configuración de Mensualidades
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <x-input-label value="Saldo a Financiar (Automático)" />
                            <div class="text-xl font-bold text-blue-900 p-2 bg-blue-100 rounded" x-text="'$' + (total_amount - down_payment_total).toFixed(2)"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <x-input-label value="Cant. Cuotas" />
                                <x-text-input type="number" class="block w-full border-blue-300 focus:border-blue-500 focus:ring-blue-500" x-model="regular_count" />
                            </div>
                            <div>
                                <x-input-label value="Fecha 1ra Mensualidad" />
                                <x-text-input type="date" class="block w-full border-blue-300 focus:border-blue-500 focus:ring-blue-500" x-model="regular_start_date" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-center">
                <button type="button" @click="generateInstallments" class="w-full md:w-1/3 px-6 py-3 bg-gray-800 text-white font-bold rounded-lg hover:bg-gray-700 shadow-lg transition transform hover:scale-105 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Generar Vista Previa
                </button>
            </div>
        </div>

        <!-- Vista Previa Editable -->
        <div x-show="generated" class="mt-8 border-t border-gray-200 pt-6" 
             x-data="{ bulk_regular: '', applyBulkRegular() { if(this.bulk_regular) { const val = parseFloat(this.bulk_regular).toFixed(2); this.installments.forEach(i => { if(!i.is_down_payment) i.amount = val }) } } }">
            
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-3">
                <h4 class="font-bold text-gray-800 text-lg">Vista Previa del Plan</h4>
                
                <!-- Herramienta de Edición Masiva -->
                <div class="flex items-center gap-2 bg-blue-50 p-2 rounded-lg border border-blue-100 shadow-sm">
                    <span class="text-xs font-bold text-blue-800 uppercase tracking-wide">Editar mensualidades a:</span>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-500 text-xs">$</span>
                        <input type="number" x-model="bulk_regular" class="w-24 text-sm border-blue-300 rounded-md p-1 pl-4 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="button" @click="applyBulkRegular" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-500 font-semibold shadow-sm transition-colors">APLICAR</button>
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto border border-gray-300 rounded-lg shadow-inner bg-white">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold">#</th>
                            <th class="px-4 py-3 text-left font-bold">Concepto</th>
                            <th class="px-4 py-3 text-left font-bold">Vencimiento</th>
                            <th class="px-4 py-3 text-left font-bold">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="(inst, index) in installments" :key="index">
                            <tr :class="inst.is_down_payment ? 'bg-yellow-50' : 'hover:bg-gray-50 transition-colors'">
                                <td class="px-4 py-2 font-bold text-gray-500">
                                    <span x-text="inst.number"></span>
                                    <input type="hidden" :name="'numbers[' + index + ']'" :value="inst.number">
                                </td>
                                <td class="px-4 py-2">
                                    <span x-text="inst.type" :class="inst.is_down_payment ? 'text-yellow-700 font-bold uppercase text-xs tracking-wide' : 'text-gray-600'"></span>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="date" :name="'due_dates[' + index + ']'" x-model="inst.due_date" class="w-full border-gray-300 bg-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm py-1">
                                </td>
                                <td class="px-4 py-2">
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2">
                                            <span class="text-gray-500 sm:text-sm font-bold">$</span>
                                        </div>
                                        <input type="number" step="0.01" :name="'amounts[' + index + ']'" x-model="inst.amount" class="block w-full rounded-md border-gray-300 pl-5 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-bold text-gray-800" :class="inst.is_down_payment ? 'bg-yellow-100 border-yellow-300 text-yellow-800' : ''">
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Advertencia de Suma --}}
            <div class="mt-4 p-3 rounded-md bg-orange-50 border-l-4 border-orange-500 text-orange-800 text-sm shadow-sm"
                 x-data="{ get diff() { return Math.abs(this.installments.reduce((s, i) => s + parseFloat(i.amount), 0) - this.total_amount); }, get sum() { return this.installments.reduce((s, i) => s + parseFloat(i.amount), 0); } }"
                 x-show="diff > 0.50"
                 x-transition>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p>
                        <strong>Atención:</strong> La suma de las cuotas (<span class="font-bold" x-text="'$' + sum.toFixed(2)"></span>) difiere del precio total original. 
                        Se guardará la suma de las cuotas como el nuevo total del plan.
                    </p>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <x-primary-button class="h-12 px-8 text-lg shadow-md hover:shadow-lg transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    CONFIRMAR Y CREAR PLAN
                </x-primary-button>
            </div>
        </div>
    </form>

    <!-- Lista de Planes Existentes -->
    <div class="mt-12 space-y-8">
        @forelse($lot->paymentPlans as $plan)
            @php
                // BLOQUE DE CÁLCULO QUE FALTA
                $totalInstallments = $plan->installments->count();
                $paidInstallments = $plan->installments->filter(function($inst) {
                    $totalPaid = $inst->transactions->sum('pivot.amount_applied');
                    $totalDue = ($inst->amount ?? $inst->base_amount) + $inst->interest_amount;
                    return ($totalDue - $totalPaid) <= 0.01;
                })->count();
                $progressPercentage = $totalInstallments > 0 ? ($paidInstallments / $totalInstallments) * 100 : 0;
            @endphp

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                {{-- Header del Plan con Formulario de Edición de Moneda --}}
                <div class="bg-gray-50 p-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h4 class="font-bold text-lg text-gray-800">{{ $plan->service->name }}</h4>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Total: {{ format_currency($plan->total_amount, $plan->currency) }}</span>
                            
                            <form action="{{ route('payment-plans.updateCurrency', $plan) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <select name="currency" onchange="this.form.submit()" class="text-xs border-gray-300 rounded-md p-1 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="MXN" @selected($plan->currency == 'MXN')>MXN</option>
                                    <option value="USD" @selected($plan->currency == 'USD')>USD</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    
                    @can('delete', $plan)
                        <form action="{{ route('payment-plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('¿Seguro? Esta acción es irreversible si tiene pagos.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-full transition-colors" title="Eliminar Plan">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    @endcan
                </div>
                
                {{-- Barra de Progreso --}}
                <div class="p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="font-semibold text-gray-600">Progreso de Pago</span>
                        <span class="font-bold text-green-700">{{ number_format($progressPercentage, 0) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-400 to-green-600 h-2.5 rounded-full transition-all duration-500 shadow-sm" style="width: {{ $progressPercentage }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs font-medium text-gray-500">
                        <span><span class="text-gray-800 font-bold">{{ $paidInstallments }}</span> pagadas</span>
                        <span><span class="text-gray-800 font-bold">{{ $totalInstallments - $paidInstallments }}</span> pendientes</span>
                    </div>
                </div>
                
                <div class="p-0">
                    @include('lots._installments-table', ['plan' => $plan])
                </div>
            </div>
        @empty
            <div class="border-dashed border-2 border-gray-300 rounded-xl p-12 text-center bg-gray-50/50">
                {{-- ... --}}
            </div>
        @endforelse
    </div>
</div>