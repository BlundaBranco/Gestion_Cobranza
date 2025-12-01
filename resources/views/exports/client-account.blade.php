<table>
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 16px;">Estado de Cuenta: {{ $client->name }}</th>
        </tr>
        <tr>
            <td colspan="6">Teléfono: {{ $client->phone }}</td>
        </tr>
        <tr>
            <td colspan="6">Email: {{ $client->email }}</td>
        </tr>
        <tr>
            <td colspan="6">Fecha de Emisión: {{ date('d/m/Y') }}</td>
        </tr>
        <tr><td colspan="6"></td></tr> {{-- Espacio --}}
    </thead>

    <tbody>
        @foreach ($client->lots as $lot)
            <tr>
                <td colspan="6" style="font-weight: bold; background-color: #cccccc;">
                    Lote: {{ $lot->identifier }} ({{ $lot->service->name ?? 'General' }})
                </td>
            </tr>
            
            @foreach ($lot->paymentPlans as $plan)
                <tr>
                    <td colspan="6" style="font-weight: bold;">
                        Plan: {{ $plan->service->name }} - Total: {{ format_currency($plan->total_amount, $plan->currency) }}
                    </td>
                </tr>
                <tr>
                    <th style="font-weight: bold; border: 1px solid #000000;"># Cuota</th>
                    <th style="font-weight: bold; border: 1px solid #000000;">Vencimiento</th>
                    <th style="font-weight: bold; border: 1px solid #000000;">Monto Cuota</th>
                    <th style="font-weight: bold; border: 1px solid #000000;">Intereses</th>
                    <th style="font-weight: bold; border: 1px solid #000000;">Pagado</th>
                    <th style="font-weight: bold; border: 1px solid #000000;">Adeudo</th>
                </tr>

                @foreach ($plan->installments->sortBy('installment_number') as $installment)
                    @php
                        $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                        $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                        $remaining = $totalDue - $totalPaid;
                    @endphp
                    <tr>
                        <td style="border: 1px solid #000000;">{{ $installment->installment_number }}</td>
                        <td style="border: 1px solid #000000;">{{ $installment->due_date->format('d/m/Y') }}</td>
                        <td style="border: 1px solid #000000;">{{ format_currency($installment->amount ?? $installment->base_amount, $plan->currency) }}</td>
                        <td style="border: 1px solid #000000;">{{ format_currency($installment->interest_amount, $plan->currency) }}</td>
                        <td style="border: 1px solid #000000;">{{ format_currency($totalPaid, $plan->currency) }}</td>
                        <td style="border: 1px solid #000000; color: {{ $remaining > 0.005 ? 'red' : 'green' }};">
                            {{ format_currency($remaining, $plan->currency) }}
                        </td>
                    </tr>
                @endforeach
                <tr><td colspan="6"></td></tr> {{-- Espacio entre planes --}}
            @endforeach
        @endforeach
    </tbody>
</table>