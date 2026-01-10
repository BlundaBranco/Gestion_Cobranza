<table>
    <thead>
        <!-- Encabezado del Cliente -->
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 16px; text-align: center; height: 30px; vertical-align: middle;">
                ESTADO DE CUENTA: {{ strtoupper($client->name) }}
            </th>
        </tr>
        <tr>
            <td colspan="6"><b>Teléfono:</b> {{ $client->phone ?? 'No registrado' }}</td>
        </tr>
        <tr>
            <td colspan="6"><b>Email:</b> {{ $client->email ?? 'No registrado' }}</td>
        </tr>
        <tr>
            <td colspan="6"><b>Fecha de Emisión:</b> {{ date('d/m/Y') }}</td>
        </tr>
        <tr><td colspan="6"></td></tr> <!-- Espacio -->

        <!-- RESUMEN FINANCIERO -->
        <tr>
            <th colspan="3" style="font-weight: bold; background-color: #ffebee; text-align: center; border: 1px solid #000000;">DEUDA PENDIENTE</th>
            <th colspan="3" style="font-weight: bold; background-color: #e8f5e9; text-align: center; border: 1px solid #000000;">TOTAL PAGADO</th>
        </tr>
        <tr>
            <td style="border: 1px solid #000000;">Capital:</td>
            <td colspan="2" style="border: 1px solid #000000;">${{ number_format($stats['debt_capital'], 2) }}</td>
            <td style="border: 1px solid #000000;">Capital:</td>
            <td colspan="2" style="border: 1px solid #000000;">${{ number_format($stats['paid_capital'], 2) }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000000;">Interés:</td>
            <td colspan="2" style="border: 1px solid #000000;">${{ number_format($stats['debt_interest'], 2) }}</td>
            <td style="border: 1px solid #000000;">Interés:</td>
            <td colspan="2" style="border: 1px solid #000000;">${{ number_format($stats['paid_interest'], 2) }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000000; font-weight: bold;">TOTAL:</td>
            <td colspan="2" style="border: 1px solid #000000; font-weight: bold; color: #ff0000;">${{ number_format($stats['total_debt'], 2) }}</td>
            <td style="border: 1px solid #000000; font-weight: bold;">TOTAL:</td>
            <td colspan="2" style="border: 1px solid #000000; font-weight: bold; color: #008000;">${{ number_format($stats['total_paid'], 2) }}</td>
        </tr>
        <tr><td colspan="6"></td></tr> <!-- Espacio -->
    </thead>

    <tbody>
        <!-- DETALLE DE LOTES Y PLANES -->
        @foreach ($client->lots as $lot)
            <tr>
                <td colspan="6" style="font-weight: bold; background-color: #cccccc; border: 1px solid #000000; font-size: 12px;">
                    LOTE: {{ $lot->identifier }} ({{ $lot->service->name ?? 'General' }}) - SOCIO: {{ $lot->owner->name ?? 'N/A' }}
                </td>
            </tr>
            
            @foreach ($lot->paymentPlans as $plan)
                <tr>
                    <td colspan="6" style="font-weight: bold; background-color: #f0f0f0; border-left: 1px solid #000000; border-right: 1px solid #000000;">
                        Plan: {{ $plan->service->name }} - Total Plan: {{ format_currency($plan->total_amount, $plan->currency) }}
                    </td>
                </tr>
                <tr>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;"># Cuota</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Vencimiento</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Monto Cuota</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Intereses</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Pagado</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Adeudo</th>
                </tr>

                @foreach ($plan->installments->sortBy('installment_number') as $installment)
                    @php
                        $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                        $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                        $remaining = $totalDue - $totalPaid;
                        $displayNumber = $installment->installment_number == 0 ? 'Enganche' : $installment->installment_number;
                    @endphp
                    <tr>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $displayNumber }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $installment->due_date->format('d/m/Y') }}</td>
                        <td style="border: 1px solid #000000; text-align: right;">{{ format_currency($installment->amount ?? $installment->base_amount, $plan->currency) }}</td>
                        <td style="border: 1px solid #000000; text-align: right;">{{ format_currency($installment->interest_amount, $plan->currency) }}</td>
                        <td style="border: 1px solid #000000; text-align: right;">{{ format_currency($totalPaid, $plan->currency) }}</td>
                        <td style="border: 1px solid #000000; text-align: right; font-weight: bold; color: {{ $remaining > 0.005 ? '#ff0000' : '#008000' }};">
                            {{ format_currency($remaining, $plan->currency) }}
                        </td>
                    </tr>
                @endforeach
                <tr><td colspan="6"></td></tr> {{-- Espacio entre planes --}}
            @endforeach
        @endforeach
    </tbody>
</table>