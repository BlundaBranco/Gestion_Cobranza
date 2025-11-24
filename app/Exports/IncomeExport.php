<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class IncomeExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
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
        $query = Transaction::query()
            ->with(['client', 'installments.paymentPlan.lot.owner', 'installments.paymentPlan.service'])
            ->whereBetween('payment_date', [$this->startDate, $this->endDate]);

        if ($this->ownerId) {
            $ownerId = $this->ownerId;
            $query->whereHas('installments.paymentPlan.lot', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            });
        }

        return $query->orderBy('payment_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'Folio',
            'Fecha',
            'Cliente',
            'Socio',
            'Manzana',  // Nuevo
            'Lote',     // Nuevo
            'Concepto', // Nuevo (Mensualidad)
            'Monto',
            'Notas',
        ];
    }

    public function map($transaction): array
    {
        // Obtener datos del primer lote/cuota asociado para referencia
        $firstInstallment = $transaction->installments->first();
        $lot = $firstInstallment->paymentPlan->lot ?? null;
        $ownerName = $lot->owner->name ?? 'N/A';
        
        // Construir detalle de la mensualidad (Ej: Terreno - Cuota 5)
        $concepto = 'Pago General';
        if ($firstInstallment) {
            $serviceName = $firstInstallment->paymentPlan->service->name;
            $cuotaNum = $firstInstallment->installment_number;
            // Si el número es 0 es Enganche, si no es Cuota X
            $tipoCuota = $cuotaNum == 0 ? 'Enganche' : "Cuota $cuotaNum";
            $concepto = "$serviceName - $tipoCuota";
        }

        return [
            $transaction->folio_number,
            $transaction->payment_date->format('d/m/Y'),
            $transaction->client->name,
            $ownerName,
            $lot->block_number ?? 'N/A', // Manzana
            $lot->lot_number ?? 'N/A',   // Lote
            $concepto,                   // Mensualidad/Concepto
            $transaction->amount_paid,
            $transaction->notes,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => '"$"#,##0.00_-', // Formato estándar de moneda en Excel
        ];
    }
}