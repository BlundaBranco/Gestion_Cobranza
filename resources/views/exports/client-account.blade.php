<table>
    <thead>
        <!-- Encabezado del Cliente -->
        <tr>
            <th colspan="7" style="font-weight: bold; font-size: 16px; text-align: center; height: 30px; vertical-align: middle;">
                ESTADO DE CUENTA: {{ strtoupper($client->name) }}
            </th>
        </tr>
        <tr>
            <td colspan="7"><b>Teléfono:</b> {{ $client->phone ?? 'No registrado' }} {{ $client->phone_label ? '('.$client->phone_label.')' : '' }}</td>
        </tr>
        <tr>
            <td colspan="7"><b>Email:</b> {{ $client->email ?? 'No registrado' }}</td>
        </tr>
        <tr>
            <td colspan="7"><b>Fecha de Emisión:</b> {{ date('d/m/Y') }}</td>
        </tr>
        <tr><td colspan="7"></td></tr>

        <!-- RESUMEN DINÁMICO POR MONEDA Y SERVICIO -->
        @foreach($summary as $currency => $services)
            <tr>
                <th colspan="7" style="font-weight: bold; background-color: #333333; color: #ffffff; text-align: center; border: 1px solid #000000;">
                    RESUMEN GLOBAL EN {{ $currency }}
                </th>
            </tr>
            @foreach($services as $serviceName => $stats)
                <tr>
                    <th colspan="7" style="font-weight: bold; background-color: #f0f0f0; color: #0000FF; border: 1px solid #000000;">
                        SERVICIO: {{ strtoupper($serviceName) }}
                    </th>
                </tr>
                <tr>
                    <th colspan="3" style="font-weight: bold; background-color: #ffebee; text-align: center; border: 1px solid #000000;">DEUDA PENDIENTE ({{ $currency }})</th>
                    <th colspan="4" style="font-weight: bold; background-color: #e8f5e9; text-align: center; border: 1px solid #000000;">TOTAL PAGADO ({{ $currency }})</th>
                </tr>
                <tr>
                    <td style="border: 1px solid #000000;">Capital:</td>
                    <td colspan="2" style="border: 1px solid #000000; text-align: right;">{{ format_currency($stats['debt_capital'], $currency) }}</td>
                    <td style="border: 1px solid #000000;">Capital:</td>
                    <td colspan="3" style="border: 1px solid #000000; text-align: right;">{{ format_currency($stats['paid_capital'], $currency) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000000;">Interés:</td>
                    <td colspan="2" style="border: 1px solid #000000; text-align: right;">{{ format_currency($stats['debt_interest'], $currency) }}</td>
                    <td style="border: 1px solid #000000;">Interés:</td>
                    <td colspan="3" style="border: 1px solid #000000; text-align: right;">{{ format_currency($stats['paid_interest'], $currency) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000000; font-weight: bold;">TOTAL:</td>
                    <td colspan="2" style="border: 1px solid #000000; font-weight: bold; color: #ff0000; text-align: right;">{{ format_currency($stats['total_debt'], $currency) }}</td>
                    <td style="border: 1px solid #000000; font-weight: bold;">TOTAL:</td>
                    <td colspan="3" style="border: 1px solid #000000; font-weight: bold; color: #008000; text-align: right;">{{ format_currency($stats['total_paid'], $currency) }}</td>
                </tr>
                <tr><td colspan="7"></td></tr>
            @endforeach
        @endforeach
    </thead>

    <tbody>
        <!-- DETALLE DESGLOSADO POR LOTE -->
        @foreach ($client->lots as $lot)
            <tr>
                <td colspan="7" style="font-weight: bold; background-color: #cccccc; border: 2px solid #000000; font-size: 12px;">
                    UBICACIÓN: MANZANA {{ $lot->block_number }} - LOTE {{ $lot->lot_number }} | SOCIO: {{ $lot->owner->name ?? 'N/A' }}
                </td>
            </tr>
            
            @foreach ($lot->paymentPlans as $plan)
                <tr>
                    <td colspan="7" style="font-weight: bold; background-color: #ffffff; border-left: 1px solid #000000; border-right: 1px solid #000000; color: #b0120a;">
                        PLAN DE PAGO: {{ strtoupper($plan->service->name) }} | VALOR TOTAL: {{ format_currency($plan->total_amount, $plan->currency) }}
                    </td>
                </tr>
                <tr>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;"># Cuota</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Vencimiento</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Monto Cuota</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Intereses</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Fecha Pago</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Pagado</th>
                    <th style="font-weight: bold; border: 1px solid #000000; background-color: #e0e0e0; text-align: center;">Adeudo</th>
                </tr>

                @foreach ($plan->installments->sortBy('installment_number') as $installment)
                    @php
                        $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                        $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                        $remaining = $totalDue - $totalPaid;
                        $displayNumber = $installment->installment_number == 0 ? 'Enganche' : $installment->installment_number;
                        $paymentDates = $installment->transactions->pluck('payment_date')->map(fn($d) => $d->format('d/m/Y'))->join(', ');
                    @endphp
                    <tr>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $displayNumber }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $installment->due_date->format('d/m/Y') }}</td>
                        <td style="border: 1px solid #000000; text-align: right;">{{ format_currency($installment->amount ?? $installment->base_amount, $plan->currency) }}</td>
                        <td style="border: 1px solid #000000; text-align: right;">{{ format_currency($installment->interest_amount, $plan->currency) }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $paymentDates ?: '-' }}</td>
                        <td style="border: 1px solid #000000; text-align: right;">{{ format_currency($totalPaid, $plan->currency) }}</td>
                        <td style="border: 1px solid #000000; text-align: right; font-weight: bold; color: {{ $remaining > 0.005 ? '#ff0000' : '#008000' }};">
                            {{ format_currency($remaining, $plan->currency) }}
                        </td>
                    </tr>
                @endforeach
                <tr><td colspan="7" style="height: 10px;"></td></tr>
            @endforeach
        @endforeach
    </tbody>
</table>