<?php

namespace App\Exports;

use App\Models\Installment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverdueInstallmentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $ownerId;
    protected $blockNumber;

    public function __construct($ownerId = null, $blockNumber = null)
    {
        $this->ownerId = $ownerId;
        $this->blockNumber = $blockNumber;
    }

    public function query()
    {
        return Installment::where('status', 'vencida')
            ->with(['paymentPlan.lot.client', 'paymentPlan.lot', 'transactions'])
            ->when($this->ownerId, fn($q, $v) => $q->whereHas('paymentPlan.lot', fn($sq) => $sq->where('owner_id', $v)))
            ->when($this->blockNumber, fn($q, $v) => $q->whereHas('paymentPlan.lot', fn($sq) => $sq->where('block_number', 'like', "%$v%")))
            ->orderBy('due_date', 'asc');
    }

    public function headings(): array
    {
        return ['CLIENTE', 'MZ', 'LOTE', 'CUOTA #', 'VENCIMIENTO', 'MONEDA', 'ADEUDO'];
    }

    public function map($installment): array
    {
        $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
        $totalPaid = $installment->transactions->sum('pivot.amount_applied');
        $remaining = $totalDue - $totalPaid;

        $lot = $installment->paymentPlan->lot ?? null;

        return [
            $lot->client->name ?? 'N/A',
            $lot->block_number ?? 'N/A',
            $lot->lot_number ?? 'N/A',
            $installment->installment_number,
            $installment->due_date->format('d/m/Y'),
            $installment->paymentPlan->currency ?? 'MXN',
            round($remaining, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
