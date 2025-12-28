<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - {{ $transaction->folio_number }}</title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; }
        .receipt-box { border: 2px solid #000; padding: 15px; height: 480px; position: relative; }
        
        /* Header */
        .header { display: table; width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header-col { display: table-cell; vertical-align: middle; }
        
        .company-details { text-align: center; font-size: 11px; font-weight: bold; line-height: 1.3; }
        .folio-box { border: 2px solid #000; padding: 5px; text-align: center; float: right; width: 120px; }
        
        /* Cuerpo */
        .body-section { margin-top: 15px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 6px 4px; border-bottom: 1px solid #666; vertical-align: top; }
        
        .label { font-weight: bold; width: 110px; }
        .content { font-family: 'Courier New', Courier, monospace; font-weight: bold; font-size: 12px; }
        
        /* Tablas internas */
        .lote-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .lote-table td { border: 1px solid #666; text-align: center; padding: 4px; font-size: 11px; font-family: 'Helvetica', sans-serif; }
        .lote-header { background-color: #f0f0f0; font-weight: bold; }

        /* Tabla de Desglose (Capital vs Interes) */
        .breakdown-table { width: 100%; margin-bottom: 10px; border-collapse: collapse; font-size: 10px; }
        .breakdown-table th { border-bottom: 1px dashed #999; text-align: left; padding: 2px; }
        .breakdown-table td { padding: 2px; font-family: 'Courier New', Courier, monospace; }

        /* Footer */
        .footer-section { position: absolute; bottom: 15px; width: 95%; display: table; }
        .footer-col { display: table-cell; width: 50%; vertical-align: bottom; }
        
        .signature-line { border-top: 1px solid #000; margin-top: 50px; width: 200px; text-align: center; padding-top: 5px; font-size: 10px; }
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
        
        // Formatear números de pago (0 se convierte en 'E')
        $pagoNum = $installments->pluck('installment_number')
            ->map(fn($num) => $num == 0 ? 'E' : $num)
            ->join(', ');
            
        $pagoTotal = $firstInstallment->paymentPlan->number_of_installments ?? 'N/A';
        
        // Concepto base
        $nombreServicio = $firstInstallment->paymentPlan->service->name ?? 'Pago';
        $conceptoTexto = $transaction->notes ? $transaction->notes : $nombreServicio;
    @endphp

    <div class="receipt-box">
        <!-- HEADER -->
        <div class="header">
            <div class="header-col" style="width: 20%;">
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
                    <div style="font-size: 14px; font-weight: bold; color: #c0392b; margin-top: 2px;">
                        No. {{ $transaction->folio_number }}
                    </div>
                </div>
            </div>
        </div>

        <!-- CUERPO -->
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
                    <td class="label" style="padding-top: 10px;">Por cuenta de:</td>
                    <td class="content">
                        <!-- 1. Texto del concepto -->
                        <div style="margin-bottom: 10px;">{{ $conceptoTexto }}</div>

                        <!-- 2. Tabla de Desglose (Lo que pidió Yanet) -->
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>Concepto (Mes)</th>
                                    <th>Capital</th>
                                    <th>Interés</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($installments as $installment)
                                    @php
                                        // Calcular desglose para ESTE pago
                                        $paidPrev = $installment->transactions->where('id', '<', $transaction->id)->sum('pivot.amount_applied');
                                        $interestTotal = $installment->interest_amount;
                                        $interestPending = max(0, $interestTotal - $paidPrev);
                                        
                                        $applied = $installment->pivot->amount_applied;
                                        $interestPaid = min($applied, $interestPending);
                                        $capitalPaid = $applied - $interestPaid;

                                        $mes = ucfirst($installment->due_date->translatedFormat('F Y'));
                                        $num = $installment->installment_number == 0 ? 'Eng' : '#'.$installment->installment_number;
                                    @endphp
                                    <tr>
                                        <td>{{ $num }} - {{ $mes }}</td>
                                        <td>{{ format_currency($capitalPaid, $currency) }}</td>
                                        <td>{{ format_currency($interestPaid, $currency) }}</td>
                                        <td style="text-align: right;">{{ format_currency($applied, $currency) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- 3. Tabla de Ubicación (Lote/Mz) -->
                        <table class="lote-table">
                            <tr class="lote-header">
                                <td>Lote #</td>
                                <td>Mz #</td>
                                <td>Pago #</td>
                            </tr>
                            <tr>
                                <td>{{ $lote }}</td>
                                <td>{{ $manzana }}</td>
                                <td>{{ $pagoNum }} de {{ $pagoTotal }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- FOOTER -->
        <div class="footer-section">
            <div class="footer-col">
                <div class="signature-box">
                    <span style="font-weight: bold;">Recibió:</span> 
                    <span class="content" style="margin-left: 5px;">{{ $transaction->user->name ?? config('app.name') }}</span>
                    <div class="signature-line">Firma</div>
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