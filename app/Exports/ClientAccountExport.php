<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClientAccountExport implements FromView, ShouldAutoSize
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function view(): \Illuminate\Contracts\View\View
    {
        // Cargar relaciones aquí
        $this->client->load([
            'lots.paymentPlans.service',
            'lots.paymentPlans.installments.transactions',
        ]);

        // Calcular Estadísticas (misma lógica que en ClientController)
        $stats = [
            'debt_capital' => 0, 'debt_interest' => 0, 'total_debt' => 0,
            'paid_capital' => 0, 'paid_interest' => 0, 'total_paid' => 0,
        ];
        // ... (Pega aquí la lógica de cálculo de stats del ClientController)

        return view('exports.client-account', [
            'client' => $this->client,
            'stats' => $stats // Pasar las stats a la vista de exportación
        ]);
    }
}