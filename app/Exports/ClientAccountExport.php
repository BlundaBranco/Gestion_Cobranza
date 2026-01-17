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
        // 1. Cargar relaciones necesarias
        $this->client->load([
            'lots.paymentPlans.service',
            'lots.paymentPlans.installments.transactions',
        ]);

        // 2. Estructura de sumatoria: [Moneda][Servicio] = [totales]
        $summary = [];

        foreach ($this->client->lots as $lot) {
            foreach ($lot->paymentPlans as $plan) {
                $currency = $plan->currency ?? 'MXN';
                $serviceName = $plan->service->name ?? 'General';

                if (!isset($summary[$currency][$serviceName])) {
                    $summary[$currency][$serviceName] = [
                        'debt_capital' => 0,
                        'debt_interest' => 0,
                        'total_debt' => 0,
                        'paid_capital' => 0,
                        'paid_interest' => 0,
                        'total_paid' => 0,
                        'total_plan_original' => 0
                    ];
                }

                $summary[$currency][$serviceName]['total_plan_original'] += $plan->total_amount;

                foreach ($plan->installments as $installment) {
                    $baseAmount = $installment->amount ?? $installment->base_amount;
                    $interestAmount = $installment->interest_amount;
                    $totalAmount = $baseAmount + $interestAmount;
                    
                    $totalPaidForInstallment = $installment->transactions->sum('pivot.amount_applied');

                    // Cálculo de lo pagado (Interés primero)
                    $paidInterest = min($totalPaidForInstallment, $interestAmount);
                    $paidCapital = $totalPaidForInstallment - $paidInterest;

                    $summary[$currency][$serviceName]['paid_interest'] += $paidInterest;
                    $summary[$currency][$serviceName]['paid_capital'] += $paidCapital;
                    $summary[$currency][$serviceName]['total_paid'] += $totalPaidForInstallment;

                    // Cálculo de deuda
                    $remainingTotal = $totalAmount - $totalPaidForInstallment;

                    if ($remainingTotal > 0.005) {
                        $summary[$currency][$serviceName]['debt_interest'] += ($interestAmount - $paidInterest);
                        $summary[$currency][$serviceName]['debt_capital'] += ($baseAmount - $paidCapital);
                        $summary[$currency][$serviceName]['total_debt'] += $remainingTotal;
                    }
                }
            }
        }

        return view('exports.client-account', [
            'client' => $this->client,
            'summary' => $summary
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}