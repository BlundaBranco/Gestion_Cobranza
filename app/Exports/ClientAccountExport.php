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
        $this->client->load([
            'lots.paymentPlans.service',
            'lots.paymentPlans.installments.transactions',
        ]);

        // Per-lot summaries: [lot_id][currency] = [totals]
        $lotSummaries = [];

        foreach ($this->client->lots as $lot) {
            $lotSummaries[$lot->id] = [];

            foreach ($lot->paymentPlans as $plan) {
                $currency = $plan->currency ?? 'MXN';

                if (!isset($lotSummaries[$lot->id][$currency])) {
                    $lotSummaries[$lot->id][$currency] = [
                        'debt_capital'            => 0,
                        'debt_interest'           => 0,
                        'total_debt'              => 0,
                        'paid_capital'            => 0,
                        'paid_interest'           => 0,
                        'total_paid'              => 0,
                        'total_from_installments' => 0,
                    ];
                }

                foreach ($plan->installments as $installment) {
                    $base     = $installment->amount ?? $installment->base_amount;
                    $interest = $installment->interest_amount;
                    $paid     = $installment->transactions->sum('pivot.amount_applied');

                    $lotSummaries[$lot->id][$currency]['total_from_installments'] += $base;

                    $paidInterest = min($paid, $interest);
                    $paidCapital  = $paid - $paidInterest;

                    $lotSummaries[$lot->id][$currency]['paid_interest'] += $paidInterest;
                    $lotSummaries[$lot->id][$currency]['paid_capital']  += $paidCapital;
                    $lotSummaries[$lot->id][$currency]['total_paid']    += $paid;

                    $remaining = ($base + $interest) - $paid;
                    if ($remaining > 0.005) {
                        $lotSummaries[$lot->id][$currency]['debt_interest'] += ($interest - $paidInterest);
                        $lotSummaries[$lot->id][$currency]['debt_capital']  += ($base - $paidCapital);
                        $lotSummaries[$lot->id][$currency]['total_debt']    += $remaining;
                    }
                }
            }
        }

        return view('exports.client-account', [
            'client'       => $this->client,
            'lotSummaries' => $lotSummaries,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}