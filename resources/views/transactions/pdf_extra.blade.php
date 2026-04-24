<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Cobro Extra - {{ $transaction->folio_number }}</title>
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

        .cut-line { border: 0; border-top: 1px dashed #999; margin: 10px 0; }
        .cut-label { text-align: center; font-size: 9px; color: #777; margin: 2px 0 6px 0; letter-spacing: 2px; }
        .copy-tag { position: absolute; top: 4px; right: 6px; font-size: 9px; color: #777; letter-spacing: 1px; }
        .extra-tag { position: absolute; top: 4px; left: 15px; font-size: 10px; font-weight: bold; color: #b45309; background: #fef3c7; padding: 2px 8px; border: 1px solid #fcd34d; border-radius: 3px; }

        .header { display: table; width: 100%; border-bottom: 2px solid #000; padding-bottom: 8px; margin-top: 22px; }
        .header-col { display: table-cell; vertical-align: middle; }

        .company-details { text-align: center; font-size: 11px; font-weight: bold; line-height: 1.3; }
        .folio-box { border: 2px solid #000; padding: 4px; text-align: center; float: right; width: 120px; }

        .body-section { margin-top: 10px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 4px 4px; border-bottom: 1px solid #666; vertical-align: top; }

        .label { font-weight: bold; width: 130px; }
        .content { font-family: 'Courier New', Courier, monospace; font-weight: bold; font-size: 12px; }

        .concepto-box { border: 1px solid #999; padding: 8px; margin-top: 8px; background: #fafafa; min-height: 40px; font-family: 'Courier New', Courier, monospace; font-size: 12px; }

        .signature-line { border-top: 1px solid #000; margin-top: 18px; width: 180px; text-align: center; padding-top: 3px; font-size: 10px; }
    </style>
</head>
<body>
    @php
        \Carbon\Carbon::setLocale('es');
        $ownerName = $transaction->owner->name ?? 'N/A';
        $currency = $transaction->currency ?? 'MXN';
        $copies = ['ORIGINAL - EMPRESA', 'COPIA - CLIENTE'];
    @endphp

    @foreach ($copies as $copyIndex => $copyLabel)
        <div class="receipt-box">
            <span class="extra-tag">COBRO EXTRA</span>
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
                                    <td class="label">Emitido por:</td>
                                    <td class="content">{{ $ownerName }}</td>
                                </tr>
                                <tr>
                                    <td class="label" style="vertical-align: top;">Por concepto de:</td>
                                    <td>
                                        <div class="concepto-box">{{ $transaction->notes }}</div>
                                        <div style="margin-top: 6px; font-size: 10px; color: #777; font-style: italic;">
                                            Este recibo no aplica a cuotas ni reduce deuda de plan de pago.
                                        </div>
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
