<?php

if (!function_exists('number_to_words_es')) {
    function number_to_words_es(float $number): string
    {
        try {
            $integerPart = intval($number);
            $decimalPart = round(($number - $integerPart) * 100);
            $formattedDecimal = str_pad($decimalPart, 2, '0', STR_PAD_LEFT);

            if ($integerPart == 0) {
                return 'Cero con ' . $formattedDecimal . '/100';
            }

            $unidades = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
            $decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
            $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
            $excepciones = [11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince'];

            $num_letra = '';
            $num_miles = floor($integerPart / 1000000);
            $integerPart = $integerPart % 1000000;

            if ($num_miles > 0) {
                if ($num_miles == 1) $num_letra .= 'un millón ';
                else $num_letra .= number_to_words_es_recursive($num_miles) . ' millones ';
            }

            $num_miles = floor($integerPart / 1000);
            $integerPart = $integerPart % 1000;

            if ($num_miles > 0) {
                if ($num_miles == 1) $num_letra .= 'mil ';
                else $num_letra .= number_to_words_es_recursive($num_miles) . ' mil ';
            }
            
            if ($integerPart > 0) {
                 $num_letra .= number_to_words_es_recursive($integerPart);
            }
            
            // Reemplazar 'uno' al final por 'un' si no es el único número
            if (substr($num_letra, -3) === 'uno' && strlen($num_letra) > 3) {
                 $num_letra = substr($num_letra, 0, -1);
            }

            return ucfirst(trim($num_letra)) . ' con ' . $formattedDecimal . '/100';

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en number_to_words_es', [
                'number' => $number,
                'error' => $e->getMessage()
            ]);
            return (string) $number;
        }
    }
}

if (!function_exists('number_to_words_es_recursive')) {
    function number_to_words_es_recursive($n) {
        $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
        $excepciones = [11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince'];

        $c = floor($n / 100);
        $d = floor(($n % 100) / 10);
        $u = $n % 10;
        $res = '';

        if ($n == 100) return 'cien';

        if ($c > 0) $res .= $centenas[$c] . ' ';
        
        $decenaUnidad = $d * 10 + $u;
        if ($decenaUnidad > 10 && $decenaUnidad < 16) {
            $res .= $excepciones[$decenaUnidad];
        } else if ($decenaUnidad == 10) {
            $res .= 'diez';
        } else if ($decenaUnidad > 15 && $decenaUnidad < 20) {
            $res .= 'dieci' . $unidades[$u];
        } else if ($decenaUnidad == 20) {
            $res .= 'veinte';
        } else if ($decenaUnidad > 20 && $decenaUnidad < 30) {
            $res .= 'veinti' . $unidades[$u];
        } else if ($decenaUnidad >= 30) {
            $res .= $decenas[$d] . ($u > 0 ? ' y ' . $unidades[$u] : '');
        } else if ($u > 0) {
            $res .= $unidades[$u];
        }
        return trim($res);
    }
}


if (!function_exists('format_currency')) {
    function format_currency(float $amount, ?string $currency = 'MXN'): string
    {
        if (empty($currency)) {
            $currency = 'MXN';
        }
        return '$' . number_format($amount, 2) . ' ' . strtoupper($currency);
    }
}

if (!function_exists('generate_whatsapp_message')) {
    function generate_whatsapp_message(\App\Models\Installment $installment, float $remaining): string
    {
        $lot = $installment->paymentPlan->lot;
        $client = $lot->client;
        if (!$client || !$client->phone) return '#';

        $statusMessage = $installment->status === 'vencida' ? "se encuentra VENCIDA" : "está PENDIENTE de pago";
        $message = "Hola {$client->name}, le recordamos que la cuota nro {$installment->installment_number} del lote {$lot->identifier}, con vencimiento el {$installment->due_date->format('d/m/Y')}, {$statusMessage} con un adeudo de " . number_format($remaining, 2) . " pesos..\n\n¿Podrías confirmarme qué día de la semana estarías pasando a realizar tu pago pendiente?";
        
        $phoneNumber = preg_replace('/[^0-9]/', '', $client->phone);
        if (strlen($phoneNumber) == 10) $phoneNumber = '52' . $phoneNumber;

        return 'https://wa.me/' . $phoneNumber . '?text=' . urlencode($message);
    }
}