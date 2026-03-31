<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            💳 Registrar Nuevo Pago
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">
                <form method="POST" action="{{ route('transactions.store') }}">
                    @csrf

                    @if ($errors->any())
                        <div class="mx-8 mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm font-semibold text-red-800">No se pudo registrar el pago:</p>
                            <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="p-8 md:p-10"
                         x-data="paymentForm" x-init="init()">

                        <!-- Header Section -->
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-indigo-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </span>
                                Información del Pago
                            </h3>
                            <p class="mt-2 text-sm text-gray-600 ml-12">Selecciona un cliente para ver y seleccionar sus cuotas pendientes.</p>
                        </div>

                        <!-- Payment Information Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" >
                            <div class="w-full">
                                <x-input-label for="client_id" value="Cliente" class="text-sm font-semibold text-gray-700" />
                                <div class="mt-2">
                                    <select x-model="clientId" @input="fetchInstallments()" id="client_id" name="client_id" class="select2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all duration-200" required>
                                        <option value="">Seleccione o busque un cliente</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" @selected(old('client_id', $selectedClientId ?? '') == $client->id)>{{ $client->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <x-input-label for="amount_paid" value="Monto a Pagar (efectivo)" class="text-sm font-semibold text-gray-700" />
                                <div class="mt-2 relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-medium">$</span>
                                    <x-text-input x-model="amountPaid" id="amount_paid" name="amount_paid" type="number" step="0.01" min="0" class="pl-7 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all duration-200 font-semibold text-lg" @input="onAmountInput()" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="payment_date" value="Fecha de Pago" class="text-sm font-semibold text-gray-700" />
                                <div class="mt-2">
                                    <x-text-input id="payment_date" name="payment_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all duration-200" value="{{ date('Y-m-d') }}" required />
                                </div>
                            </div>
                        </div>

                        <!-- Saldo a Favor disponible -->
                        <template x-if="creditBalance > 0">
                            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-emerald-100 rounded-lg">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-emerald-800">Saldo a favor disponible</p>
                                        <p class="text-xs text-emerald-600">Este cliente tiene <span class="font-bold" x-text="`$${creditBalance.toFixed(2)}`"></span> a su favor</p>
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" name="apply_credit" value="1" x-model="applyCredit" @change="updateTotal()" class="w-5 h-5 rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                                    <span class="text-sm font-semibold text-emerald-800">Aplicar en este pago</span>
                                </label>
                            </div>
                        </template>

                        <!-- Installments Section -->
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </span>
                                        Cuotas Pendientes
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-600 ml-10">Selecciona las cuotas a pagar.</p>
                                </div>
                                <div class="w-full sm:w-auto">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </span>
                                        <input type="text" x-model="searchQuery" placeholder="Buscar por servicio o lote..." class="w-full sm:w-72 pl-10 pr-4 py-2 border-gray-300 rounded-lg text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all duration-200">
                                    </div>
                                </div>
                            </div>

                            <!-- Loading State -->
                            <div x-show="loading" class="text-center p-12 bg-white rounded-lg">
                                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-gray-200 border-t-indigo-600"></div>
                                <p class="mt-4 text-gray-500 font-medium">Cargando cuotas...</p>
                            </div>

                            <!-- Installments Table -->
                            <div x-show="!loading && filteredInstallments.length > 0" class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                <div class="max-h-96 overflow-y-auto">
                                    <table class="w-full text-sm">
                                        <thead class="text-xs font-semibold text-gray-700 uppercase bg-gradient-to-r from-gray-100 to-gray-50 sticky top-0 z-10">
                                            <tr>
                                                <th class="p-4 w-12 text-center">
                                                    <span class="sr-only">Seleccionar</span>
                                                </th>
                                                <th class="p-4 text-left">Servicio / Lote</th>
                                                <th class="p-4 text-center"># Cuota</th>
                                                <th class="p-4 text-left">Vencimiento</th>
                                                <th class="p-4 text-right">Adeudo</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="item in groupedInstallments" :key="item._isHeader ? item._id : String(item.id)">
                                                <tr
                                                    :class="item._isHeader
                                                        ? 'bg-indigo-50 border-t-2 border-indigo-200'
                                                        : 'hover:bg-indigo-50 transition-colors duration-150 cursor-pointer'"
                                                    @click="!item._isHeader && $el.querySelector('input[type=checkbox]')?.click()">

                                                    <td x-show="item._isHeader" colspan="5"
                                                        class="px-4 py-2 text-xs font-bold text-indigo-700 uppercase tracking-wider"
                                                        x-text="item._label ?? ''"></td>

                                                    <td x-show="!item._isHeader" class="p-4 text-center">
                                                        <input type="checkbox" name="installments[]"
                                                            :value="item._isHeader ? '' : item.id"
                                                            :disabled="item._isHeader"
                                                            x-model="selectedInstallments"
                                                            @change="!item._isHeader && updateTotal()"
                                                            class="w-5 h-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-2 focus:ring-indigo-500 cursor-pointer transition-all duration-200">
                                                    </td>
                                                    <td x-show="!item._isHeader" class="p-4 text-gray-900 font-medium"
                                                        x-text="item._isHeader ? '' : item.payment_plan.service.name"></td>
                                                    <td x-show="!item._isHeader" class="p-4 text-center">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800"
                                                            x-text="item._isHeader ? '' : item.installment_number"></span>
                                                    </td>
                                                    <td x-show="!item._isHeader" class="p-4 text-gray-700"
                                                        x-text="item._isHeader ? '' : item.formatted_due_date"></td>
                                                    <td x-show="!item._isHeader" class="p-4 text-right">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-lg font-bold text-red-700 bg-red-50"
                                                            x-text="item._isHeader ? '' : `$${parseFloat(item.remaining_balance).toFixed(2)}`"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div x-show="!loading && clientId && filteredInstallments.length === 0" class="mt-4 text-center py-12 bg-white border-2 border-dashed border-gray-300 rounded-xl">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-4 text-gray-500 font-medium">
                                    <span x-show="searchQuery !== ''">No hay cuotas que coincidan con la búsqueda.</span>
                                    <span x-show="searchQuery === ''">Este cliente no tiene cuotas pendientes.</span>
                                </p>
                            </div>
                        </div>

                        <!-- Panel: Excedente de pago -->
                        <template x-if="cashExcess > 0.005 && selectedInstallments.length > 0">
                            <div class="mt-6 p-5 bg-amber-50 border border-amber-200 rounded-xl">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <p class="text-sm font-bold text-amber-800">
                                        Excedente: <span x-text="`$${cashExcess.toFixed(2)}`"></span>
                                    </p>
                                </div>
                                <p class="text-xs text-amber-700 mb-4">El monto ingresado supera el total de las cuotas seleccionadas. ¿Qué hacemos con el excedente?</p>
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <label class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all duration-150"
                                        :class="excessAction === 'credit' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                                        <input type="radio" name="excess_action" value="credit" x-model="excessAction" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">Registrar como saldo a favor</p>
                                            <p class="text-xs text-gray-500 mt-0.5">El excedente queda a nombre del cliente y se puede aplicar en pagos futuros.</p>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all duration-150"
                                        :class="excessAction === 'none' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                                        <input type="radio" name="excess_action" value="none" x-model="excessAction" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">Abonar a la siguiente cuota</p>
                                            <p class="text-xs text-gray-500 mt-0.5">Seleccioná también la siguiente cuota en la lista y el sistema aplica el excedente como abono parcial.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </template>

                        <!-- Notes Section -->
                        <div class="mt-8">
                            <x-input-label for="notes" value="Notas (Opcional)" class="text-sm font-semibold text-gray-700" />
                            <div class="mt-2">
                                <textarea id="notes" name="notes" rows="3" class="block w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all duration-200" placeholder="Agrega cualquier observación adicional sobre este pago..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-4 bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 border-t border-gray-200">
                        <x-secondary-button as="a" href="{{ route('transactions.index') }}" class="px-6 py-2.5 rounded-lg font-semibold transition-all duration-200 hover:scale-105">
                            Cancelar
                        </x-secondary-button>
                        <x-primary-button class="px-6 py-2.5 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Registrar Pago y Generar Folio
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('paymentForm', () => ({
                clientId: '{{ old('client_id', $selectedClientId ?? '') }}',
                amountPaid: 0,
                installments: [],
                selectedInstallments: [],
                loading: false,
                searchQuery: '',
                creditBalance: 0,
                applyCredit: false,
                excessAction: 'none',

                init() {
                    const self = this;

                    $('#client_id').select2({
                        theme: "classic",
                        placeholder: "Selecciona o busca un cliente",
                        allowClear: true
                    }).on('change', function () {
                        self.clientId = $(this).val();
                        self.fetchInstallments();
                    });

                    if (this.clientId) {
                        $('#client_id').val(this.clientId).trigger('change.select2');
                        this.fetchInstallments();
                    } else {
                        this.installments = [];
                    }
                },

                fetchInstallments() {
                    if (!this.clientId) {
                        this.installments = [];
                        this.creditBalance = 0;
                        this.applyCredit = false;
                        this.updateTotal();
                        return;
                    }
                    this.loading = true;
                    this.selectedInstallments = [];
                    this.searchQuery = '';
                    this.applyCredit = false;

                    fetch(`/clients/${this.clientId}/pending-installments`)
                        .then(response => {
                            if (!response.ok) throw new Error('Error al cargar cuotas (HTTP ' + response.status + ')');
                            return response.json();
                        })
                        .then(data => {
                            this.installments    = Array.isArray(data.installments) ? data.installments : [];
                            this.creditBalance   = data.credit_balance || 0;
                            this.loading         = false;

                            const preselectedId = '{{ $selectedInstallmentId ?? '' }}';
                            if (preselectedId && this.installments.some(inst => inst.id == preselectedId)) {
                                this.selectedInstallments.push(preselectedId);
                            }

                            this.updateTotal();
                        })
                        .catch(err => {
                            this.loading = false;
                            alert('No se pudieron cargar las cuotas. Recargá la página e intentá de nuevo.\n\nDetalle: ' + err.message);
                        });
                },

                onAmountInput() {
                    // When user manually types a cash amount, recalculate excess
                    this.$nextTick(() => this.updateExcess());
                },

                updateTotal() {
                    const totalRequired = this.installments
                        .filter(inst => this.selectedInstallments.includes(inst.id.toString()))
                        .reduce((sum, inst) => sum + parseFloat(inst.remaining_balance), 0);

                    const creditContrib = this.applyCredit ? this.creditBalance : 0;
                    // Auto-fill cash amount needed (remaining after applying credit)
                    this.amountPaid = Math.max(0, totalRequired - creditContrib).toFixed(2);
                    this.updateExcess();
                },

                updateExcess() {
                    // Reset excess action when there's no excess
                    if (this.cashExcess <= 0.005) {
                        this.excessAction = 'none';
                    }
                },

                get cashExcess() {
                    const totalRequired = this.installments
                        .filter(inst => this.selectedInstallments.includes(inst.id.toString()))
                        .reduce((sum, inst) => sum + parseFloat(inst.remaining_balance), 0);
                    const totalPool = parseFloat(this.amountPaid || 0) + (this.applyCredit ? this.creditBalance : 0);
                    return Math.max(0, totalPool - totalRequired);
                },

                get filteredInstallments() {
                    if (this.searchQuery.trim() === '') {
                        return this.installments;
                    }
                    const searchTerm = this.searchQuery.toLowerCase();
                    return this.installments.filter(inst => {
                        const lotIdentifier  = inst.payment_plan.lot.identifier.toLowerCase();
                        const serviceName    = inst.payment_plan.service.name.toLowerCase();
                        const installmentNumber = inst.installment_number.toString();
                        return lotIdentifier.includes(searchTerm) ||
                            serviceName.includes(searchTerm) ||
                            installmentNumber.includes(searchTerm);
                    });
                },

                get groupedInstallments() {
                    const result = [];
                    const seen   = new Set();
                    (this.filteredInstallments || []).forEach(inst => {
                        const key = inst.payment_plan?.lot?.identifier ?? 'Sin lote';
                        if (!seen.has(key)) {
                            seen.add(key);
                            result.push({ _isHeader: true, _label: key, _id: 'hdr-' + key });
                        }
                        result.push(inst);
                    });
                    return result;
                }
            }));
        });
    </script>
    @endpush

</x-app-layout>
