<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class IncomeExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnFormatting
{
    protected $startDate;
    protected $endDate;
    protected $ownerId;

    public function __construct($startDate, $endDate, $ownerId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->ownerId = $ownerId;
    }

    public function query()
    {
        return Transaction::query()
            ->with([
                'client',
                'installments.paymentPlan.lot',
                'installments.transactions',
            ])
            ->whereBetween('payment_date', [$this->startDate, $this->endDate])
            ->when($this->ownerId, function ($query) {
                $query->whereHas('installments.paymentPlan.lot', fn ($q) => $q->where('owner_id', $this->ownerId));
            })
            ->orderBy('payment_date', 'desc');
    }

    public function headings(): array
    {
        return ['NOMBRE', 'LOTE', 'MZ', 'DLLS', 'PESOS', 'FECHA', 'INT. DLL', 'INT. PESO', 'MENSUALIDAD'];
    }

    public function map($transaction): array
    {
        $capitalPaid  = 0;
        $interestPaid = 0;

        $firstInstallment = $transaction->installments->first();
        $currency = $firstInstallment?->paymentPlan->currency ?? 'MXN';
        $lot      = $firstInstallment?->paymentPlan->lot ?? null;

        foreach ($transaction->installments as $installment) {
            $interestAmount = (float) $installment->interest_amount;
            $amountApplied  = (float) $installment->pivot->amount_applied;

            // Sum the interest already covered by transactions that preceded this one
            $priorInterestPaid = 0;
            $priorTransactions = $installment->transactions
                ->where('id', '!=', $transaction->id)
                ->sortBy(fn ($tx) => $tx->payment_date->timestamp * 1_000_000 + $tx->id);

            foreach ($priorTransactions as $priorTx) {
                $pendingInterest    = max(0, $interestAmount - $priorInterestPaid);
                $priorInterestPaid += min((float) $priorTx->pivot->amount_applied, $pendingInterest);
            }

            $pendingInterest        = max(0, $interestAmount - $priorInterestPaid);
            $interestInCurrentTx    = min($amountApplied, $pendingInterest);
            $capitalInCurrentTx     = $amountApplied - $interestInCurrentTx;

            $capitalPaid  += $capitalInCurrentTx;
            $interestPaid += $interestInCurrentTx;
        }

        if ($currency === 'USD') {
            $dlls    = $capitalPaid;
            $pesos   = 0;
            $intDll  = $interestPaid;
            $intPeso = 0;
        } else {
            $dlls    = 0;
            $pesos   = $capitalPaid;
            $intDll  = 0;
            $intPeso = $interestPaid;
        }

        return [
            $transaction->client->name,
            $lot->lot_number   ?? 'N/A',
            $lot->block_number ?? 'N/A',
            $dlls,
            $pesos,
            $transaction->payment_date->format('d/m/Y'),
            $intDll,
            $intPeso,
            $capitalPaid,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_ACCOUNTING_USD,
            'E' => NumberFormat::FORMAT_ACCOUNTING_USD,
            'G' => NumberFormat::FORMAT_ACCOUNTING_USD,
            'H' => NumberFormat::FORMAT_ACCOUNTING_USD,
        ];
    }
}
