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
        $firstInstallment = $transaction->installments->first();
        $lot = $firstInstallment->paymentPlan->lot ?? null;
        
        $manzana = $lot->block_number ?? 'N/A';
        $lote = $lot->lot_number ?? 'N/A';
        $pagoNum = $firstInstallment->installment_number ?? 'N/A';
        $pagoTotal = $firstInstallment->paymentPlan->number_of_installments ?? 'N/A';
    @endphp
    <div class="receipt-box">
        <div class="header">
            <div class="header-col" style="width: 100px;">
                {{-- Colocar aquí la imagen del logo si se tiene --}}
                {{-- <img src="{{ public_path('logo.png') }}" alt="Logo" class="logo"> --}}
                <span class="logo" style="font-weight: bold; font-size:14px;">LOMAS DEL PACIFICO</span>
            </div>
            <div class="header-col company-details">
                COL. LOMAS DEL PACIFICO<br>
                Tel. Oficina: 664-383-1246<br>
                Col. Roberto Yahuaca, Calle Brisas del Mar<br>
                L-13 Mz-7 C.P. 22545 Tijuana, B.C.
            </div>
            <div class="header-col" style="width: 120px;">
                <div class="folio-box">
                    RECIBO DE PAGO<br>
                    <span style="font-weight:bold; font-size:16px; color: red;">No. {{ $transaction->folio_number }}</span>
                </div>
            </div>
        </div>

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
                    <td class="content">{{ number_to_words_es($transaction->amount_paid) }}</td>
                </tr>
                <tr>
                    <td class="label" style="vertical-align: top;">Por cuenta de:</td>
                    <td class="content">
                        {{-- Se usa el campo de notas para este propósito --}}
                        {{ $transaction->notes ?? ($transaction->installments->first()->paymentPlan->service->name ?? 'Pago de mensualidad') }}
                        <table class="lote-table">
                            <tr>
                                <td>lote #</td>
                                <td>Mz#</td>
                                <td>Pago#</td>
                            </tr>
                            <tr>
                                <td class="content">{{ $lote }}</td>
                                <td class="content">{{ $manzana }}</td>
                                <td class="content">{{ $pagoNum }} de {{ $pagoTotal }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer-section">
            <div class="footer-col">
                <div class="signature-box">
                    Recibió:
                    <span class="content">{{ $transaction->user->name ?? config('app.name') }}</span>
                    <div class="signature-line">
                        Firma
                    </div>
                </div>
            </div>
            <div class="footer-col">
                <div class="amount-box">
                    Por $ <span class="content" style="border-bottom: 1px solid #666; padding: 0 10px;">{{ number_format($transaction->amount_paid, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>