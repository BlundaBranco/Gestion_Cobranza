<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lot;
use Carbon\Carbon;

class FixInstallmentDates extends Command
{
    protected $signature = 'installments:fix-dates';
    protected $description = 'Corrige las fechas de vencimiento según el número de manzana (1-9: día 10, 10+: día 30)';

    public function handle()
    {
        $this->info('Iniciando corrección de fechas...');
        
        // Cargar lotes con sus planes y cuotas
        $lots = Lot::with('paymentPlans.installments')->get();
        $count = 0;

        foreach ($lots as $lot) {
            // 1. Determinar el número de manzana
            // Usamos regex para sacar el número de strings tipo "Manzana 1", "Mz 1", "1", etc.
            preg_match('/(\d+)/', $lot->block_number, $matches);
            $blockNum = isset($matches[1]) ? intval($matches[1]) : 0;

            // 2. Determinar el día objetivo
            // Regla: Manzanas 10 en adelante -> Día 30. Manzanas 1-9 -> Día 10.
            $targetDay = ($blockNum >= 10) ? 30 : 10;

            foreach ($lot->paymentPlans as $plan) {
                foreach ($plan->installments as $installment) {
                    // 3. Construir la nueva fecha
                    $originalDate = Carbon::parse($installment->due_date);
                    
                    // Creamos una fecha con el mismo año y mes, pero con el día objetivo
                    $newDate = Carbon::create($originalDate->year, $originalDate->month, $targetDay);

                    // Validación de Carbon: Si le pides día 30 de Febrero, Carbon lo desborda a Marzo.
                    // Nosotros queremos que se quede en fin de mes.
                    if ($newDate->month != $originalDate->month) {
                        // Si cambió de mes, significa que el día no existe (ej 30 feb).
                        // Retrocedemos al último día del mes original.
                        $newDate = Carbon::create($originalDate->year, $originalDate->month)->endOfMonth();
                    }

                    // Guardar solo si es diferente
                    if ($installment->due_date->format('Y-m-d') !== $newDate->format('Y-m-d')) {
                        $installment->due_date = $newDate;
                        $installment->save();
                        $count++;
                    }
                }
            }
        }

        $this->info("Proceso terminado. Se actualizaron las fechas de {$count} cuotas.");
        
        // Recalcular estados (vencidas/intereses) con las nuevas fechas
        $this->call('installments:update-status');
        
        return 0;
    }
}