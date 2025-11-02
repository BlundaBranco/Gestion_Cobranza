<!-- _payment-plans-section.blade.php - Con resumen visual -->
<div class="flex items-center gap-3 mb-6">
    <div class="p-2 bg-green-100 rounded-lg">
        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>
    </div>
    <div>
        <h3 class="text-lg font-bold text-gray-900">Planes de Pago</h3>
        <p class="text-sm text-gray-600">Gestiona los planes de pago del lote</p>
    </div>
</div>

<form method="POST" action="{{ route('lots.payment-plans.store', $lot) }}" class="border-t-2 border-gray-100 pt-6">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div>
            <x-input-label for="service_id" value="Servicio" />
            <select name="service_id" id="service_id" class="mt-1 block w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150" required>
                @foreach(\App\Models\Service::all() as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="total_amount" value="Monto Total" />
            <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="number_of_installments" value="No. de Cuotas" />
            <x-text-input id="number_of_installments" name="number_of_installments" type="number" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="start_date" value="Fecha de Inicio" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="date('Y-m-d')" required />
        </div>
    </div>
    <div class="flex justify-end mt-6">
        <x-primary-button>
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crear Nuevo Plan
        </x-primary-button>
    </div>
</form>

@foreach($lot->paymentPlans as $plan)
    @php
        $totalInstallments = $plan->installments->count();
        $paidInstallments = $plan->installments->filter(function($inst) {
            $totalPaid = $inst->transactions->sum('pivot.amount_applied');
            $totalDue = $inst->base_amount + $inst->interest_amount;
            return ($totalDue - $totalPaid) <= 0.005;
        })->count();
        $progressPercentage = $totalInstallments > 0 ? ($paidInstallments / $totalInstallments) * 100 : 0;
    @endphp
    
    <div class="mt-8 rounded-xl border-2 border-gray-200 overflow-hidden">
        <!-- Header del Plan con Resumen -->
        <div class="bg-gradient-to-br from-gray-50 to-blue-50 p-6 border-b-2 border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-lg font-bold text-gray-900">{{ $plan->service->name }}</h4>
                    <p class="text-sm text-gray-600">{{ $totalInstallments }} cuotas totales</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Monto Total</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($plan->total_amount, 2) }}</p>
                </div>
            </div>
            
            <!-- Barra de Progreso -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="font-semibold text-gray-700">Progreso del Plan</span>
                    <span class="font-bold text-gray-900">{{ $paidInstallments }} / {{ $totalInstallments }} cuotas pagadas</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300" style="width: {{ $progressPercentage }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-600">
                    <span>{{ number_format($progressPercentage, 1) }}% completado</span>
                    <span>{{ $totalInstallments - $paidInstallments }} cuotas pendientes</span>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Cuotas -->
        <div class="bg-white">
            @include('lots._installments-table', ['plan' => $plan])
        </div>
    </div>
@endforeach