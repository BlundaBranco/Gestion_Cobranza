<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Registrar Nuevo Pago</h2>
                <p class="text-sm text-gray-600">Aplica un pago a cuotas pendientes</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 overflow-hidden">
                <form method="POST" action="{{ route('transactions.store') }}">
                    @csrf
                    <div class="p-6 md:p-8" 
                         x-data="{ 
                            clientId: '{{ $selectedClientId ?? '' }}', 
                            amountPaid: 0,
                            installments: [],
                            selectedInstallments: [],
                            loading: false,
                            init() { if (this.clientId) this.fetchInstallments(); },
                            fetchInstallments() {
                                if (!this.clientId) { this.installments = []; this.updateTotal(); return; }
                                this.loading = true; this.selectedInstallments = [];
                                fetch(`/clients/${this.clientId}/pending-installments`)
                                    .then(response => response.json())
                                    .then(data => {
                                        this.installments = data; this.loading = false;
                                        const preselectedId = '{{ $selectedInstallmentId ?? '' }}';
                                        if (preselectedId && this.installments.some(inst => inst.id == preselectedId)) {
                                            this.selectedInstallments.push(preselectedId);
                                        }
                                        this.updateTotal();
                                    });
                            },
                            updateTotal() {
                                this.amountPaid = this.installments
                                    .filter(inst => this.selectedInstallments.includes(inst.id.toString()))
                                    .reduce((sum, inst) => sum + parseFloat(inst.remaining_balance), 0).toFixed(2);
                            }
                         }" x-init="init()">
                        
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Información del Pago</h3>
                                <p class="text-sm text-gray-600">Selecciona un cliente para ver sus cuotas pendientes.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 border-t-2 border-gray-100 pt-6">
                            <div>
                                <x-input-label for="client_id" value="Cliente" />
                                <select x-model="clientId" @change="fetchInstallments()" id="client_id" name="client_id" class="mt-1 block w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150" required>
                                    <option value="">Seleccione un cliente</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" @selected($selectedClientId == $client->id)>{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="amount_paid" value="Monto a Pagar" />
                                <x-text-input x-model="amountPaid" id="amount_paid" name="amount_paid" type="number" step="0.01" class="mt-1 block w-full" required readonly />
                            </div>
                            <div>
                                <x-input-label for="payment_date" value="Fecha de Pago" />
                                <x-text-input id="payment_date" name="payment_date" type="date" class="mt-1 block w-full" value="{{ date('Y-m-d') }}" required />
                            </div>
                        </div>

                        <div class="border-t-2 border-gray-100 pt-6 mt-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Cuotas Pendientes</h3>
                                    <p class="text-sm text-gray-600">Selecciona las cuotas a pagar</p>
                                </div>
                            </div>
                            
                            <div x-show="loading" class="text-center p-8 bg-gray-50 rounded-xl border-2 border-gray-200">
                                <div class="inline-flex items-center gap-3">
                                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-gray-700 font-semibold">Cargando cuotas...</span>
                                </div>
                            </div>
                            
                            <div x-show="!loading && installments.length > 0" class="border-2 border-gray-200 rounded-xl overflow-hidden max-h-96 overflow-y-auto">
                                <table class="w-full text-sm">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b-2 border-gray-200 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-3 w-12"></th>
                                            <th class="px-4 py-3 text-left font-bold">Servicio / Lote</th>
                                            <th class="px-4 py-3 text-left font-bold"># Cuota</th>
                                            <th class="px-4 py-3 text-left font-bold">Vencimiento</th>
                                            <th class="px-4 py-3 text-left font-bold">Adeudo</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <template x-for="inst in installments" :key="inst.id">
                                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                <td class="px-4 py-3">
                                                    <input type="checkbox" name="installments[]" :value="inst.id" x-model="selectedInstallments" @change="updateTotal()" class="w-4 h-4 rounded border-2 border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2 cursor-pointer">
                                                </td>
                                                <td class="px-4 py-3 text-gray-900 font-semibold" x-text="`${inst.payment_plan.service.name} (${inst.payment_plan.lot.identifier})`"></td>
                                                <td class="px-4 py-3 text-gray-700" x-text="inst.installment_number"></td>
                                                <td class="px-4 py-3 text-gray-700" x-text="inst.formatted_due_date"></td>
                                                <td class="px-4 py-3">
                                                    <span class="font-bold text-red-600" x-text="`$${parseFloat(inst.remaining_balance).toFixed(2)}`"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div x-show="!loading && clientId && installments.length === 0" class="text-center py-12 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                                <div class="flex flex-col items-center">
                                    <div class="p-3 bg-green-100 rounded-full mb-3">
                                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-900 font-semibold mb-1">Sin cuotas pendientes</p>
                                    <p class="text-sm text-gray-600">Este cliente no tiene cuotas pendientes de pago</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t-2 border-gray-100 pt-6 mt-6">
                            <x-input-label for="notes" value="Notas (Opcional)" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150" placeholder="Agrega notas adicionales sobre el pago..."></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 bg-gradient-to-br from-gray-50 to-blue-50 px-6 py-4 border-t-2 border-gray-100">
                        <a href="{{ route('transactions.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white border-2 border-gray-300 rounded-xl font-semibold text-sm text-gray-700 hover:bg-gray-50 transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar
                        </a>
                        <x-primary-button>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Registrar Pago y Generar Folio
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>