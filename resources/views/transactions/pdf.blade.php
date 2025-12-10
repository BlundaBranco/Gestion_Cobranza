<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - {{ $transaction->folio_number }}</title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; }
        .receipt-box { border: 2px solid #000; padding: 15px; }
        
        /* Header usando display table para columnas perfectas en PDF */
        .header { display: table; width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header-col { display: table-cell; vertical-align: middle; }
        
        .company-details { text-align: center; font-size: 12px; font-weight: bold; line-height: 1.4; }
        .folio-box { border: 2px solid #000; padding: 5px 10px; text-align: center; float: right; }
        
        .body-section { margin-top: 15px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 6px 4px; border-bottom: 1px solid #666; }
        
        .label { font-weight: bold; width: 110px; }
        .content { font-family: 'Courier New', Courier, monospace; font-weight: bold; }
        
        .lote-table { width: 100%; margin-top: 5px; }
        .lote-table td { border: 1px solid #666; text-align: center; padding: 6px; }
        
        .footer-section { margin-top: 25px; display: table; width: 100%; }
        .footer-col { display: table-cell; width: 50%; vertical-align: bottom; }
        
        .signature-box { margin-top: 10px; }
        .signature-line { border-top: 1px solid #000; margin-top: 40px; width: 200px; text-align: center; padding-top: 5px; font-size: 10px; }
        
        .amount-box { text-align: right; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
    @php
        \Carbon\Carbon::setLocale('es');
        
        // Obtener cuotas ordenadas
        $installments = $transaction->installments->sortBy('installment_number');
        $firstInstallment = $installments->first();
        
        // Datos generales
        $lot = $firstInstallment->paymentPlan->lot ?? null;
        $currency = $firstInstallment->paymentPlan->currency ?? 'MXN';
        $manzana = $lot->block_number ?? 'N/A';
        $lote = $lot->lot_number ?? 'N/A';
        
        // Formatear números de pago (0 se convierte en 'E' de Enganche)
        $pagoNum = $installments->pluck('installment_number')
            ->map(fn($num) => $num == 0 ? 'E' : $num)
            ->join(', ');
            
        $pagoTotal = $firstInstallment->paymentPlan->number_of_installments ?? 'N/A';
        
        // Generar lista de meses (ej: Octubre 2025)
        $meses = $installments->sortBy('due_date')
            ->map(fn($inst) => ucfirst($inst->due_date->translatedFormat('F Y')))
            ->unique()
            ->join(', ');
            
        // Definir concepto
        $nombreServicio = $firstInstallment->paymentPlan->service->name ?? 'Pago';
        $conceptoFinal = $transaction->notes ? $transaction->notes : "$nombreServicio (Mensualidad: $meses)";
    @endphp

    <div class="receipt-box">
        <!-- HEADER CON LOGO -->
        <div class="header">
            <div class="header-col" style="width: 20%;">
                {{-- Logo: Ruta absoluta para DOMPDF --}}
                <img src="{{ public_path('img/logo.png') }}" alt="Logo" style="width: 80px; height: auto;">
            </div>
            
            <div class="header-col company-details" style="width: 55%;">
                COL. LOMAS DEL PACIFICO<br>
                Tel. Oficina: 664-383-1246<br>
                Col. Roberto Yahuaca, Calle Brisas del Mar<br>
                L-13 Mz-7 C.P. 22545 Tijuana, B.C.
            </div>
            
            <div class="header-col" style="width: 25%;">
                <div class="folio-box">
                    <div style="font-size: 10px; font-weight: bold;">RECIBO DE PAGO</div>
                    <div style="font-size: 16px; font-weight: bold; color: #c0392b; margin-top: 2px;">
                        No. {{ $transaction->folio_number }}
                    </div>
                </div>
            </div>
        </div>

        <!-- CUERPO DEL RECIBO -->
        <div class="body-section">
            <table class="data-table">
                <tr>
                    <td class="label">Día / Mes / Año:</td>
                    <td class="content">{{ $transaction->payment_date->format('d / m / Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Recibí de:</td>
                    <td class="content" style="text-transform: uppercase;">{{ $transaction->client->name }}</td>
                </tr>
                <tr>
                    <td class="label">La cantidad de:</td>
                    <td class="content">{{ number_to_words_es($transaction->amount_paid) }} ({{ $currency }})</td>
                </tr>
                <tr>
                    <td class="label" style="vertical-align: top; padding-top: 10px;">Por cuenta de:</td>
                    <td class="content">
                        <div style="font-size: 11px; margin-bottom: 5px;">{{ $conceptoFinal }}</div>
                        
                        <table class="lote-table">
                            <tr>
                                <td style="background-color: #f0f0f0;">lote #</td>
                                <td style="background-color: #f0f0f0;">Mz#</td>
                                <td style="background-color: #f0f0f0;">Pago#</td>
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

        <!-- FOOTER / FIRMA -->
        <div class="footer-section">
            <div class="footer-col">
                <div class="signature-box">
                    <span style="font-weight: bold;">Recibió:</span> 
                    <span class="content" style="margin-left: 5px;">{{ $transaction->user->name ?? config('app.name') }}</span>
                    <div class="signature-line">
                        Firma
                    </div>
                </div>
            </div>
            <div class="footer-col">
                <div class="amount-box">
                    Por <span class="content" style="border-bottom: 1px solid #666; padding: 0 10px; min-width: 100px; display: inline-block; text-align: center;">
                        {{ format_currency($transaction->amount_paid, $currency) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>