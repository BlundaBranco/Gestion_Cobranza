<?php

use NumberToWords\NumberToWords;

if (!function_exists('number_to_words_es')) {
    function number_to_words_es(float $number): string
    {
        $numberToWords = new NumberToWords();
        $transformer = $numberToWords->getNumberTransformer('es');

        $integerPart = intval($number);
        $decimalPart = round(($number - $integerPart) * 100);

        $words = $transformer->toWords($integerPart);
        $formattedDecimal = str_pad($decimalPart, 2, '0', STR_PAD_LEFT);

        return ucfirst($words) . ' pesos ' . $formattedDecimal . '/100 M.N.';
    }
}

if (!function_exists('generate_whatsapp_message')) {
    function generate_whatsapp_message(\App\Models\Installment $installment, float $remaining): string
    {
        $lot = $installment->paymentPlan->lot;
        $client = $lot->client;

        if (!$client || !$client->phone) {
            return '#'; // Devuelve un enlace inofensivo si no hay cliente o teléfono
        }

        $clientName = $client->name;
        $lotIdentifier = $lot->identifier;
        $installmentNumber = $installment->installment_number;
        $dueDate = $installment->due_date->format('d/m/Y');
        $remainingFormatted = number_format($remaining, 2);

        // Lógica condicional para el estado
        $statusMessage = '';
        if ($installment->status === 'vencida') {
            $statusMessage = "se encuentra VENCIDA";
        } else { // 'pendiente'
            $statusMessage = "está PENDIENTE de pago";
        }

        // Mensaje simplificado para máxima compatibilidad
        $message = "Hola {$clientName}, le recordamos que la cuota nro {$installmentNumber} del lote {$lotIdentifier}, con vencimiento el {$dueDate}, {$statusMessage} con un adeudo de {$remainingFormatted} pesos.";
        
        // Limpieza y formato del número de teléfono
        $phoneNumber = preg_replace('/[^0-9]/', '', $client->phone);
        
        // Añadir código de país de México si es necesario
        if (strlen($phoneNumber) == 10) {
            $phoneNumber = '52' . $phoneNumber;
        }

        return 'https://wa.me/' . $phoneNumber . '?text=' . urlencode($message);
    }
}