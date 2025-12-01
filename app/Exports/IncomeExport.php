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
        // Precargar todas las relaciones necesarias
        return Transaction::query()
            ->with(['client', 'installments.paymentPlan.lot.owner', 'installments.paymentPlan.service'])
            ->whereBetween('payment_date', [$this->startDate, $this->endDate])
            ->when($this->ownerId, function ($query) {
                $query->whereHas('installments.paymentPlan.lot', fn ($q) => $q->where('owner_id', $this->ownerId));
            })
            ->orderBy('payment_date', 'desc');
    }

    public function headings(): array
    {
        return ['Folio', 'Fecha', 'Cliente', 'Socio', 'Manzana', 'Lote', 'Concepto', 'Monto', 'Moneda', 'Notas'];
    }

    public function map($transaction): array
    {
        $installments = $transaction->installments->sortBy('installment_number');
        $firstInstallment = $installments->first();
        $lot = $firstInstallment->paymentPlan->lot ?? null;
        
        // Generar concepto con múltiples cuotas
        $concepto = $installments->map(function ($inst) {
            $num = $inst->installment_number == 0 ? 'E' : $inst->installment_number;
            return "{$inst->paymentPlan->service->name} (Cuota {$num})";
        })->unique()->join(', ');

        return [
            $transaction->folio_number,
            $transaction->payment_date->format('d/m/Y'),
            $transaction->client->name,
            $lot->owner->name ?? 'N/A',
            $lot->block_number ?? 'N/A',
            $lot->lot_number ?? 'N/A',
            $concepto,
            $transaction->amount_paid,
            $firstInstallment->paymentPlan->currency ?? 'MXN', // Moneda
            $transaction->notes,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function columnFormats(): array
    {
        // Columna H = Monto
        return ['H' => NumberFormat::FORMAT_ACCOUNTING_USD];
    }
}