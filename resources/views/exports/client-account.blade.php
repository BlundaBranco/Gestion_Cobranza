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
                        Plan: {{ $plan->service->name }} - Total: ${{ number_format($plan->total_amount, 2) }}
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
                        <td style="border: 1px solid #000000;">${{ number_format($installment->amount ?? $installment->base_amount, 2) }}</td>
                        <td style="border: 1px solid #000000;">${{ number_format($installment->interest_amount, 2) }}</td>
                        <td style="border: 1px solid #000000;">${{ number_format($totalPaid, 2) }}</td>
                        <td style="border: 1px solid #000000; color: {{ $remaining > 0.005 ? 'red' : 'green' }};">
                            ${{ number_format($remaining, 2) }}
                        </td>
                    </tr>
                @endforeach
                <tr><td colspan="6"></td></tr> {{-- Espacio entre planes --}}
            @endforeach
        @endforeach
    </tbody>
</table>