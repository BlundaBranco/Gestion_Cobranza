<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - {{ $transaction->folio_number }}</title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; }
        .receipt-box { border: 2px solid #000; padding: 15px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header-col { display: table-cell; vertical-align: middle; }
        .logo { width: 80px; }
        .company-details { text-align: center; font-size: 12px; font-weight: bold; }
        .folio-box { border: 2px solid #000; padding: 5px 10px; text-align: center; float: right; }
        .body-section { margin-top: 15px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 6px 4px; border-bottom: 1px solid #666; }
        .label { font-weight: bold; width: 110px; }
        .content { font-family: 'Courier New', Courier, monospace; }
        .lote-table { width: 100%; margin-top: 5px; }
        .lote-table td { border: 1px solid #666; text-align: center; padding: 6px; }
        .footer-section { margin-top: 25px; display: table; width: 100%; }
        .footer-col { display: table-cell; width: 50%; }
        .signature-line { border-top: 1px solid #000; margin-top: 30px; text-align: center; padding-top: 5px; }
        .amount-box { float: right; font-weight: bold; }
    </style>
</head>
<body>
    @php
        \Carbon\Carbon::setLocale('es');
        $installments = $transaction->installments->sortBy('installment_number');
        $firstInstallment = $installments->first();
        $lot = $firstInstallment->paymentPlan->lot ?? null;
        $currency = $firstInstallment->paymentPlan->currency ?? 'MXN';
        $manzana = $lot->block_number ?? 'N/A';
        $lote = $lot->lot_number ?? 'N/A';
        $pagoNum = $installments->pluck('installment_number')->map(fn($num) => $num == 0 ? 'E' : $num)->join(', ');
        $pagoTotal = $firstInstallment->paymentPlan->number_of_installments ?? 'N/A';
        $meses = $installments->sortBy('due_date')->map(fn($inst) => ucfirst($inst->due_date->translatedFormat('F Y')))->unique()->join(', ');
        $nombreServicio = $firstInstallment->paymentPlan->service->name ?? 'Pago';
        $conceptoFinal = $transaction->notes ? $transaction->notes : "$nombreServicio (Mensualidad: $meses)";
    @endphp

    <div class="receipt-box">
        {{-- ... header sin cambios ... --}}
        <div class="body-section">
            <table class="data-table">
                <tr>
                    <td class="label">Día / Mes / Año:</td>
                    <td class="content">{{ $transaction->payment_date->format('d / m / Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Recibí de:</td>
                    <td class="content">{{ $transaction->client->name }}</td>
                </tr>
                <tr>
                    <td class="label">La cantidad de:</td>
                    <td class="content">{{ number_to_words_es($transaction->amount_paid) }} ({{ $currency }})</td>
                </tr>
                <tr>
                    <td class="label" style="vertical-align: top;">Por cuenta de:</td>
                    <td class="content" style="font-size: 10px;">
                        {{ $conceptoFinal }}
                        <table class="lote-table">
                            <tr><td>lote #</td><td>Mz#</td><td>Pago#</td></tr>
                            <tr><td class="content">{{ $lote }}</td><td class="content">{{ $manzana }}</td><td class="content">{{ $pagoNum }} de {{ $pagoTotal }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
        <div class="footer-section">
            <div class="footer-col">
                <div class="signature-box">
                    Recibió: <span class="content">{{ $transaction->user->name ?? config('app.name') }}</span>
                    <div class="signature-line">Firma</div>
                </div>
            </div>
            <div class="footer-col">
                <div class="amount-box">
                    <span class="content" style="border-bottom: 1px solid #666; padding: 0 10px;">{{ format_currency($transaction->amount_paid, $currency) }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>