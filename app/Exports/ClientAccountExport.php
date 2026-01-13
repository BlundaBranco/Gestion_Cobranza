<?php

namespace App\Exports;

use App\Models\Client;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientAccountExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function view(): View
    {
        // 1. Cargar relaciones necesarias para el cálculo
        $this->client->load([
            'lots.paymentPlans.service',
            'lots.paymentPlans.installments.transactions',
        ]);

        // 2. Inicializar estadísticas
        $stats = [
            'debt_capital' => 0,
            'debt_interest' => 0,
            'total_debt' => 0,
            'paid_capital' => 0,
            'paid_interest' => 0,
            'total_paid' => 0,
        ];

        // 3. Iterar y calcular (Lógica idéntica al ClientController)
        foreach ($this->client->lots as $lot) {
            foreach ($lot->paymentPlans as $plan) {
                foreach ($plan->installments as $installment) {
                    // Valores base de la cuota (usando el monto editable si existe)
                    $baseAmount = $installment->amount ?? $installment->base_amount;
                    $interestAmount = $installment->interest_amount;
                    $totalAmount = $baseAmount + $interestAmount;

                    // Total pagado para esta cuota específica
                    $totalPaidForInstallment = $installment->transactions->sum('pivot.amount_applied');

                    // --- CÁLCULO DE LO PAGADO ---
                    // Regla: Se paga primero el interés, luego el capital
                    $paidInterest = min($totalPaidForInstallment, $interestAmount);
                    $paidCapital = $totalPaidForInstallment - $paidInterest;

                    // Acumular a totales globales de pagado
                    $stats['paid_interest'] += $paidInterest;
                    $stats['paid_capital'] += $paidCapital;
                    $stats['total_paid'] += $totalPaidForInstallment;

                    // --- CÁLCULO DE LA DEUDA ---
                    $remainingTotal = $totalAmount - $totalPaidForInstallment;

                    // Si hay deuda (margen de error flotante 0.005)
                    if ($remainingTotal > 0.005) {
                        $remainingInterest = $interestAmount - $paidInterest;
                        $remainingCapital = $baseAmount - $paidCapital;

                        $stats['debt_interest'] += $remainingInterest;
                        $stats['debt_capital'] += $remainingCapital;
                        $stats['total_debt'] += $remainingTotal;
                    }
                }
            }
        }

        return view('exports.client-account', [
            'client' => $this->client,
            'stats' => $stats
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Pone en negrita la fila 1 (Título)
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}