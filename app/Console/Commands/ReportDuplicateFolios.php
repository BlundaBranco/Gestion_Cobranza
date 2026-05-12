<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DuplicateFoliosExport;

class ReportDuplicateFolios extends Command
{
    protected $signature = 'folios:reporte-duplicados';
    protected $description = 'Genera un Excel con todas las transactions cuyo (owner_id, folio_number) está duplicado, para que Yanet decida qué renumerar.';

    public function handle()
    {
        $this->info('Buscando folios duplicados...');

        // Pares (owner_id, folio_number) que aparecen más de una vez
        $duplicatedPairs = DB::table('transactions')
            ->whereNotNull('folio_number')
            ->where('folio_number', 'like', 'FOLIO-%')
            ->select('owner_id', 'folio_number')
            ->groupBy('owner_id', 'folio_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicatedPairs->isEmpty()) {
            $this->info('No hay folios duplicados. Nada que reportar.');
            return 0;
        }

        $this->info("Encontrados {$duplicatedPairs->count()} pares duplicados. Cargando detalle...");

        // Construyo OR conditions para traer todas las transactions afectadas
        $rows = DB::table('transactions')
            ->leftJoin('owners', 'owners.id', '=', 'transactions.owner_id')
            ->leftJoin('clients', 'clients.id', '=', 'transactions.client_id')
            ->whereIn(DB::raw('CONCAT(transactions.owner_id, "|", transactions.folio_number)'),
                $duplicatedPairs->map(fn($p) => $p->owner_id . '|' . $p->folio_number)->toArray()
            )
            ->orderBy('transactions.owner_id')
            ->orderBy('transactions.folio_number')
            ->orderBy('transactions.id')
            ->select(
                'transactions.id',
                'transactions.folio_number',
                'transactions.amount_paid',
                'transactions.payment_date',
                'transactions.created_at',
                'transactions.deleted_at',
                'transactions.notes',
                'owners.name as owner_name',
                'clients.name as client_name',
            )
            ->get();

        $this->info("Total de transactions afectadas: {$rows->count()}");

        $filename = 'duplicados-folios-' . now()->format('Y-m-d-His') . '.xlsx';
        Excel::store(new DuplicateFoliosExport($rows), $filename, 'local');

        $fullPath = storage_path('app/private/' . $filename);
        $altPath = storage_path('app/' . $filename);
        $shown = file_exists($fullPath) ? $fullPath : $altPath;

        $this->info("Excel generado en: {$shown}");
        $this->info('Pásalo a Yanet para que marque cuál es el correcto en cada caso (columna "¿CUÁL ES EL CORRECTO?").');

        return 0;
    }
}
