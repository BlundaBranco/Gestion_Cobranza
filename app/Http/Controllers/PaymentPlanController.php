<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\PaymentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class PaymentPlanController extends Controller
{
    /**
     * Almacena un nuevo plan de pago creado manualmente desde la interfaz.
     */
    public function store(Request $request, \App\Models\Lot $lot)
    {
        // 1. Validar SOLO los datos que sí llegan del formulario
        // Quitamos 'start_date' y 'number_of_installments' porque los calculamos abajo
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'currency' => 'required|in:MXN,USD',
            'amounts'    => 'required|array',
            'amounts.*'  => 'required|numeric|min:0',
            'due_dates'  => 'required|array',
            'due_dates.*'=> 'required|date',
            'numbers'    => 'required|array',
            'numbers.*'  => 'required',
        ]);

        // 2. Calcular los datos derivados automáticamente
        
        // El número real de cuotas es el tamaño del array
        $realCount = count($validated['amounts']); 
        
        // El total real es la suma del array
        $realTotal = array_sum($validated['amounts']); 
        
        // La fecha de inicio es la fecha más antigua del array de vencimientos
        $dates = $validated['due_dates'];
        sort($dates); // Ordenar ascendente
        $realStartDate = $dates[0];

        // 3. Validar duplicados
        $exists = \App\Models\PaymentPlan::where('lot_id', $lot->id)
            ->where('service_id', $validated['service_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Ya existe un plan de pago para este lote y servicio.');
        }

        try {
            DB::beginTransaction();

            // 4. Crear el Plan de Pago con los datos calculados
            $paymentPlan = $lot->paymentPlans()->create([
                'service_id'             => $validated['service_id'],
                'currency'               => $validated['currency'],
                'total_amount'           => $realTotal,
                'number_of_installments' => $realCount,
                'start_date'             => $realStartDate,
            ]);

            // 5. Crear las Cuotas
            foreach ($validated['amounts'] as $index => $amount) {
                $paymentPlan->installments()->create([
                    'installment_number' => $validated['numbers'][$index],
                    'due_date'           => $validated['due_dates'][$index],
                    'amount'             => $amount,
                    'base_amount'        => $amount,
                    'status'             => 'pendiente'
                ]);
            }
            
            // 6. Actualizar estados inmediatamente (vencidas, intereses, etc)
            \Illuminate\Support\Facades\Artisan::call('installments:update-status');

            DB::commit();

            return redirect()->route('lots.edit', $lot)->with('success', 'Plan de pago creado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error técnico al crear el plan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateCurrency(Request $request, \App\Models\PaymentPlan $plan)
    {
        $validated = $request->validate([
            'currency' => 'required|in:MXN,USD',
        ]);

        $plan->update($validated);

        return back()->with('success', 'La moneda del plan de pago ha sido actualizada.');
    }


    /**
     * Elimina un plan de pago.
     */
    public function destroy(PaymentPlan $plan)
    {
        // Verificar si el plan tiene transacciones asociadas (pagos reales)
        $hasTransactions = $plan->installments()->whereHas('transactions')->exists();

        // Escenario 1: Sin pagos -> Eliminación directa permitida para todos
        if (!$hasTransactions) {
            $plan->delete();
            return back()->with('success', 'Plan de pago eliminado exitosamente.');
        }

        // Escenario 2: Con pagos -> Solo Admin puede forzar la eliminación (vía Policy)
        if ($hasTransactions && auth()->user()->can('forceDelete', $plan)) {
            $plan->delete();
            return back()->with('success', 'Plan de pago y su historial han sido eliminados forzosamente por el administrador.');
        }

        // Escenario 3: Con pagos y usuario normal -> Denegar
        return back()->with('error', 'No se puede eliminar: el plan tiene pagos registrados. Solo un administrador puede forzar esta acción.');
    }

    /**
     * Genera cuotas automáticamente dividiendo el total.
     * Método público para ser usado por Seeders o Importadores de Excel.
     */
    public function generateInstallments(PaymentPlan $paymentPlan)
    {
        $installments = [];
        // Calcular monto base con 2 decimales
        $baseAmount = round($paymentPlan->total_amount / $paymentPlan->number_of_installments, 2);
        $startDate = Carbon::parse($paymentPlan->start_date);
        
        // Calcular diferencia por redondeo para sumarla a la primera cuota
        $totalCalculated = $baseAmount * $paymentPlan->number_of_installments;
        $difference = $paymentPlan->total_amount - $totalCalculated;

        for ($i = 1; $i <= $paymentPlan->number_of_installments; $i++) {
            $currentAmount = $baseAmount;
            
            // Ajuste de centavos en la primera cuota
            if ($i === 1) {
                $currentAmount += $difference;
            }

            $installments[] = [
                'payment_plan_id' => $paymentPlan->id,
                'installment_number' => $i,
                'due_date' => $startDate->copy()->addMonths($i - 1)->toDateString(),
                'base_amount' => $currentAmount,
                'amount' => $currentAmount, // Sincronizar amount editable con base
                'status' => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('installments')->insert($installments);
    }
}