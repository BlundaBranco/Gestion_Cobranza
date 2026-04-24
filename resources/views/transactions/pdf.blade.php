<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - {{ $transaction->folio_number }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }

        .receipt-box {
            border: 2px solid #000;
            padding: 0;
            height: 355px;
            position: relative;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        .cut-line {
            border: 0;
            border-top: 1px dashed #999;
            margin: 10px 0;
            text-align: center;
            height: 0;
        }
        .cut-label {
            text-align: center;
            font-size: 9px;
            color: #777;
            margin: 2px 0 6px 0;
            letter-spacing: 2px;
        }
        .copy-tag {
            position: absolute;
            top: 4px;
            right: 6px;
            font-size: 9px;
            color: #777;
            letter-spacing: 1px;
        }

        /* Header */
        .header { display: table; width: 100%; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .header-col { display: table-cell; vertical-align: middle; }

        .company-details { text-align: center; font-size: 11px; font-weight: bold; line-height: 1.3; }
        .folio-box { border: 2px solid #000; padding: 4px; text-align: center; float: right; width: 120px; }

        /* Cuerpo Principal */
        .body-section { margin-top: 10px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 3px 4px; border-bottom: 1px solid #666; vertical-align: top; }

        .label { font-weight: bold; width: 110px; }
        .content { font-family: 'Courier New', Courier, monospace; font-weight: bold; font-size: 12px; }

        /* Tabla de Ubicación (Lote/Mz) */
        .lote-table { width: 100%; margin-top: 6px; border-collapse: collapse; }
        .lote-table td { border: 1px solid #666; text-align: center; padding: 2px; font-size: 10px; font-family: 'Helvetica', sans-serif; font-weight: bold; }
        .lote-header { background-color: #f0f0f0; }

        /* Tabla de Desglose (Capital vs Interés) */
        .breakdown-table { width: 100%; margin-bottom: 4px; border-collapse: collapse; font-size: 10px; }
        .breakdown-table th { border-bottom: 1px dashed #999; text-align: left; padding: 2px; color: #555; }
        .breakdown-table td { padding: 2px; font-family: 'Courier New', Courier, monospace; }
        .row-border { border-bottom: 1px dotted #ccc; }

        /* Footer */
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 18px;
            width: 180px;
            text-align: center;
            padding-top: 3px;
            font-size: 10px;
        }
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

        $pagoNum = $installments->pluck('installment_number')
            ->map(fn($num) => $num == 0 ? 'E' : $num)
            ->join(', ');

        $pagoTotal = $firstInstallment->paymentPlan->number_of_installments ?? 'N/A';

        $nombreServicio = $firstInstallment->paymentPlan->service->name ?? 'Pago';
        $conceptoTexto = $transaction->notes ? $transaction->notes : $nombreServicio;

        $conceptStyle = (strlen($conceptoTexto) > 80 || $installments->count() > 4) ? 'font-size: 10px;' : '';

        $copies = ['ORIGINAL - EMPRESA', 'COPIA - CLIENTE'];
    @endphp

    @foreach ($copies as $copyIndex => $copyLabel)
        <div class="receipt-box">
            <span class="copy-tag">{{ $copyLabel }}</span>

            {{-- Tabla interna: fila 1 = header+cuerpo (vertical-align top), fila 2 = footer (vertical-align bottom) --}}
            <table style="width: 100%; height: 349px; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="vertical-align: top; padding: 12px 15px 0 15px;">

                        <div class="header">
                            <div class="header-col" style="width: 20%;">
                                <img src="{{ public_path('img/logo.png') }}" alt="Logo" style="width: 70px; height: auto;">
                            </div>

                            <div class="header-col company-details" style="width: 55%;">
                                COL. LOMAS DEL PACIFICO<br>
                                Tel. Oficina: 664-383-1246<br>
                                Tel. Celular: 663-439-3311<br>
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

                        <div class="body-section">
                            <table class="data-table">
                                <tr>
                                    <td class="label">Día / Mes / Año:</td>
                                    <td class="content">{{ $transaction->payment_date->format('d / m / Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Recibí de:</td>
                                    <td class="content" style="text-transform: uppercase;">
                                        {{ $transaction->client->name }}
                                        @if($transaction->client->phone)
                                           <span style="font-size: 10px; font-weight: normal; margin-left:10px;">({{ $transaction->client->phone }})</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label">La cantidad de:</td>
                                    <td class="content" style="font-size: 11px;">{{ number_to_words_es($transaction->amount_paid) }} ({{ $currency }})</td>
                                </tr>
                                <tr>
                                    <td class="label" style="padding-top: 6px;">Por cuenta de:</td>
                                    <td class="content">
                                        <div style="margin-bottom: 6px; {{ $conceptStyle }}">{{ $conceptoTexto }}</div>

                                        <table class="breakdown-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 40%">Concepto (Mes)</th>
                                                    <th style="width: 20%">Capital</th>
                                                    <th style="width: 20%">Interés</th>
                                                    <th style="text-align: right; width: 20%">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($installments as $installment)
                                                    @php
                                                        $paidPrev = $installment->transactions->where('id', '<', $transaction->id)->sum('pivot.amount_applied');
                                                        $interestTotal = $installment->interest_amount;
                                                        $interestPending = max(0, $interestTotal - $paidPrev);

                                                        $applied = $installment->pivot->amount_applied;
                                                        $interestPaid = min($applied, $interestPending);
                                                        $capitalPaid = $applied - $interestPaid;

                                                        $mes = ucfirst($installment->due_date->translatedFormat('F Y'));
                                                        $num = $installment->installment_number == 0 ? 'Eng' : '#'.$installment->installment_number;
                                                    @endphp
                                                    <tr class="row-border">
                                                        <td>{{ $num }} - {{ $mes }}</td>
                                                        <td>{{ format_currency($capitalPaid, $currency) }}</td>
                                                        <td>{{ format_currency($interestPaid, $currency) }}</td>
                                                        <td style="text-align: right;">{{ format_currency($applied, $currency) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>

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

                    </td>
                </tr>
                <tr style="height: 75px;">
                    <td style="vertical-align: bottom; padding: 0 15px 12px 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 50%; vertical-align: bottom;">
                                    <span style="font-weight: bold;">Recibió:</span>
                                    <span class="content" style="margin-left: 5px;">{{ $transaction->user->name ?? config('app.name') }}</span>
                                    <div class="signature-line">Firma</div>
                                </td>
                                <td style="width: 50%; vertical-align: bottom; text-align: right; font-weight: bold; font-size: 13px;">
                                    Por <span class="content" style="border-bottom: 1px solid #666; padding: 0 10px; min-width: 100px; display: inline-block; text-align: center;">
                                        {{ format_currency($transaction->amount_paid, $currency) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        @if ($copyIndex === 0)
            <div class="cut-label">✂  -  -  -  -  -  LÍNEA DE CORTE  -  -  -  -  -</div>
            <hr class="cut-line">
        @endif
    @endforeach
</body>
</html>
