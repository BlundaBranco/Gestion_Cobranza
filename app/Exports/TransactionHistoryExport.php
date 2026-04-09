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

class TransactionHistoryExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnFormatting
{
    protected $search;
    protected $ownerId;

    public function __construct($search = null, $ownerId = null)
    {
        $this->search  = $search;
        $this->ownerId = $ownerId;
    }

    public function query()
    {
        $query = Transaction::withTrashed()->with([
            'client',
            'installments.paymentPlan.lot',
            'installments.transactions',
        ]);

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where('folio_number', 'like', $term)
                  ->orWhereHas('client', fn($q) => $q->where('name', 'like', $term));
        }

        if ($this->ownerId) {
            $query->whereHas('installments.paymentPlan.lot', fn($q) => $q->where('owner_id', $this->ownerId));
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return ['FOLIO', 'CLIENTE', 'LOTE', 'MZ', 'DLLS', 'PESOS', 'FECHA', 'INT. DLL', 'INT. PESO', 'MENSUALIDAD', 'ESTADO'];
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

            $priorInterestPaid = 0;
            $priorTransactions = $installment->transactions
                ->where('id', '!=', $transaction->id)
                ->sortBy(fn($tx) => $tx->payment_date->timestamp * 1_000_000 + $tx->id);

            foreach ($priorTransactions as $priorTx) {
                $pendingInterest    = max(0, $interestAmount - $priorInterestPaid);
                $priorInterestPaid += min((float) $priorTx->pivot->amount_applied, $pendingInterest);
            }

            $pendingInterest     = max(0, $interestAmount - $priorInterestPaid);
            $interestInCurrentTx = min($amountApplied, $pendingInterest);
            $capitalInCurrentTx  = $amountApplied - $interestInCurrentTx;

            $capitalPaid  += $capitalInCurrentTx;
            $interestPaid += $interestInCurrentTx;
        }

        if ($currency === 'USD') {
            $dlls = $capitalPaid; $pesos = 0; $intDll = $interestPaid; $intPeso = 0;
        } else {
            $dlls = 0; $pesos = $capitalPaid; $intDll = 0; $intPeso = $interestPaid;
        }

        $concepto = $transaction->installments
            ->map(fn($i) => \Carbon\Carbon::parse($i->due_date)->locale('es')->isoFormat('MMM YYYY'))
            ->join(', ');

        return [
            $transaction->folio_number,
            $transaction->client->name,
            $lot->lot_number   ?? 'N/A',
            $lot->block_number ?? 'N/A',
            $dlls,
            $pesos,
            $transaction->payment_date->format('d/m/Y'),
            $intDll,
            $intPeso,
            $concepto,
            $transaction->trashed() ? 'Cancelado' : 'Activo',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_ACCOUNTING_USD,
            'F' => NumberFormat::FORMAT_ACCOUNTING_USD,
            'H' => NumberFormat::FORMAT_ACCOUNTING_USD,
            'I' => NumberFormat::FORMAT_ACCOUNTING_USD,
        ];
    }
}
