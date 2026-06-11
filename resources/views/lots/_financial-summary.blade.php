{{-- Solo planes activos: la deuda de un plan cancelado (lote revendido) no suma --}}
@php $activeSummaryPlans = $lot->paymentPlans->where('status', 'active'); @endphp
@if($activeSummaryPlans->isNotEmpty())
    @php $plansByCurrency = $activeSummaryPlans->groupBy('currency'); @endphp
    @foreach ($plansByCurrency as $currency => $plans)
        @php
            $s = [
                'total_debt' => 0, 'debt_capital' => 0, 'debt_interest' => 0,
                'total_paid' => 0, 'paid_capital' => 0, 'paid_interest' => 0,
                'pending_installments' => 0, 'months_overdue' => 0,
            ];
            foreach ($plans->flatMap->installments as $inst) {
                $paid   = $inst->transactions->sum('pivot.amount_applied');
                $int    = $inst->interest_amount;
                $base   = $inst->amount ?? $inst->base_amount;
                $due    = $base + $int;
                $remain = $due - $paid;

                if ($remain > 0.01) {
                    $s['pending_installments']++;
                    if ($inst->status === 'vencida') $s['months_overdue']++;
                    $piOnDebt = min($paid, $int);
                    $s['debt_interest'] += $int - $piOnDebt;
                    $s['debt_capital']  += $base - ($paid - $piOnDebt);
                    $s['total_debt']    += $remain;
                }
                $pi = min($paid, $int);
                $s['paid_interest'] += $pi;
                $s['paid_capital']  += $paid - $pi;
                $s['total_paid']    += $paid;
            }
        @endphp

        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg border border-gray-100">
            <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-500 flex items-center justify-center text-white shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">
                        Resumen Financiero
                        @if(count($plansByCurrency) > 1)
                            <span class="text-sm font-normal text-gray-500 ml-1">— {{ $currency }}</span>
                        @endif
                    </h3>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <p class="text-xs font-medium text-red-700">Deuda Pendiente</p>
                        <p class="text-xl font-bold text-red-600">{{ format_currency($s['total_debt'], $currency) }}</p>
                        <div class="mt-1 text-xs text-gray-500 space-y-0.5">
                            <div class="flex justify-between"><span>Capital:</span><span class="font-semibold">{{ format_currency($s['debt_capital'], $currency) }}</span></div>
                            <div class="flex justify-between"><span>Interés:</span><span class="font-semibold">{{ format_currency($s['debt_interest'], $currency) }}</span></div>
                        </div>
                    </div>

                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <p class="text-xs font-medium text-green-700">Total Pagado</p>
                        <p class="text-xl font-bold text-green-600">{{ format_currency($s['total_paid'], $currency) }}</p>
                        <div class="mt-1 text-xs text-gray-500 space-y-0.5">
                            <div class="flex justify-between"><span>A Capital:</span><span class="font-semibold">{{ format_currency($s['paid_capital'], $currency) }}</span></div>
                            <div class="flex justify-between"><span>A Interés:</span><span class="font-semibold">{{ format_currency($s['paid_interest'], $currency) }}</span></div>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <p class="text-xs font-medium text-blue-700">Cuotas Pendientes</p>
                        <p class="text-xl font-bold text-blue-600">{{ $s['pending_installments'] }}</p>
                    </div>

                    <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                        <p class="text-xs font-medium text-orange-700">Meses en Mora</p>
                        <p class="text-xl font-bold text-orange-600">{{ $s['months_overdue'] }}</p>
                        <p class="text-xs text-orange-500 mt-1">Cuotas vencidas impagas</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
