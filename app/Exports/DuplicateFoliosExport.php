<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DuplicateFoliosExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'SOCIO',
            'FOLIO',
            'ID TX',
            'CLIENTE',
            'MONTO',
            'FECHA PAGO',
            'FECHA REGISTRO',
            'ESTADO',
            'NOTAS',
            '¿CUÁL ES EL CORRECTO?',
        ];
    }

    public function map($row): array
    {
        return [
            $row->owner_name ?? 'N/A',
            $row->folio_number,
            $row->id,
            $row->client_name ?? 'N/A',
            (float) $row->amount_paid,
            $row->payment_date,
            $row->created_at,
            $row->deleted_at ? 'CANCELADO' : 'ACTIVO',
            $row->notes,
            '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
