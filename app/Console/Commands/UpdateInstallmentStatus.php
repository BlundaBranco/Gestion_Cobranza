<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Installment;
use App\Models\Lot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateInstallmentStatus extends Command
{
    protected $signature = 'installments:update-status';
    protected $description = 'Actualiza cuotas a "vencida", recalcula intereses y liquida lotes pagados.';
    private const MONTHLY_INTEREST_RATE = 0.1;

    public function handle()
    {
        Log::info('Iniciando tarea programada: UpdateInstallmentStatus.');
        $this->info('Iniciando actualización de estado de cuotas y lotes...');

        DB::beginTransaction();
        try {
            // --- FASE 1: Marcar cuotas como vencidas ---
            $this->markInstallmentsAsOverdue();

            // --- FASE 2: Recalcular intereses para todas las cuotas vencidas ---
            $this->recalculateInterests();

            // --- FASE 3: Liquidar lotes completamente pagados ---
            $this->liquidatePaidLots();

            DB::commit();
            $this->info('Proceso completado exitosamente.');
            Log::info('Tarea programada: UpdateInstallmentStatus finalizada con éxito.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error durante la actualización: ' . $e->getMessage());
            Log::error('Error en UpdateInstallmentStatus', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }

    private function markInstallmentsAsOverdue()
    {
        $updatedCount = Installment::where('status', 'pendiente')
            ->where('due_date', '<', Carbon::today())
            ->update(['status' => 'vencida']);
            
        $this->info("Cuotas marcadas como 'vencida': {$updatedCount}");
    }

    private function recalculateInterests()
    {
        $interestAppliedCount = 0;
        
        // Obtener todas las cuotas vencidas que no estén completamente pagadas
        $overdueInstallments = Installment::where('status', 'vencida')
            ->with('transactions')
            ->get();

        foreach ($overdueInstallments as $installment) {
            // Usar el monto editable como base; si no existe, usar el calculado
            $baseAmount = $installment->amount ?? $installment->base_amount;
            
            // Sumar todos los pagos aplicados a esta cuota
            $totalPaid = $installment->transactions->sum('pivot.amount_applied');
            
            // El interés se calcula sobre el capital (monto base) que aún no se ha cubierto
            $capitalBalance = $baseAmount - $totalPaid;

            if ($capitalBalance > 0) {
                // CORRECCIÓN CLAVE: Reemplazar (=), no sumar (+=)
                $interest = $capitalBalance * self::MONTHLY_INTEREST_RATE;
                $installment->interest_amount = round($interest, 2);
                $interestAppliedCount++;
            } else {
                // Si el capital ya está pagado (posiblemente con pagos anteriores), el interés debería ser cero
                $installment->interest_amount = 0;
            }
            
            $installment->save();
        }

        $this->info("Cuotas con interés recalculado: {$interestAppliedCount}");
    }

    private function liquidatePaidLots()
    {
        $liquidatedLots = 0;
        
        $potentialLotsToLiquidate = Lot::where('status', 'vendido')
            ->whereDoesntHave('paymentPlans.installments', function ($query) {
                $query->whereIn('status', ['pendiente', 'vencida']);
            })
            ->has('paymentPlans')
            ->with('paymentPlans.installments.transactions')
            ->get();

        foreach ($potentialLotsToLiquidate as $lot) {
            $totalDebtForLot = 0;
            foreach ($lot->paymentPlans as $plan) {
                foreach ($plan->installments as $installment) {
                    $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                    $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                    $remaining = $totalDue - $totalPaid;
                    if ($remaining > 0.01) {
                        $totalDebtForLot += $remaining;
                    }
                }
            }

            if ($totalDebtForLot <= 0.01) {
                $lot->update(['status' => 'liquidado']);
                $liquidatedLots++;
            }
        }
        
        $this->info("Lotes liquidados en esta ejecución: {$liquidatedLots}");
    }
}