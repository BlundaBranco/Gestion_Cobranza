<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Lot;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CorrectionImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $blockNumber = trim($row['mz']);
            $lotNumber = trim($row['lote']);
            $mensualidad = floatval($row['mensualidad']);

            if (empty($blockNumber) || empty($lotNumber) || empty($mensualidad)) {
                continue;
            }

            // Encontrar el lote en la base de datos
            $lot = Lot::where('block_number', $blockNumber)
                      ->where('lot_number', $lotNumber)
                      ->first();
            
            if ($lot) {
                // Actualizar todas las cuotas de todos los planes de pago de este lote
                foreach ($lot->paymentPlans as $plan) {
                    $plan->installments()->update(['amount' => $mensualidad]);
                }
            }
        }
    }
}

class CorrectInstallmentAmounts extends Command
{
    protected $signature = 'data:correct-installments {filename}';
    protected $description = 'Actualiza los montos de las cuotas existentes desde un archivo Excel.';

    public function handle()
    {
        $filename = $this->argument('filename');
        $filePath = storage_path('app/migrations/' . $filename);

        if (!file_exists($filePath)) {
            $this->error("El archivo '{$filename}' no se encontró.");
            return 1;
        }

        $this->info("Iniciando corrección de montos desde '{$filename}'...");
        
        try {
            Excel::import(new CorrectionImport(), $filePath);
            $this->info("Corrección completada.");
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}