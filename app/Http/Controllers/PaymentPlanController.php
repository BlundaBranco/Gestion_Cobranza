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

    public function store(Request $request, Lot $lot)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'total_amount' => 'required|numeric|min:0', // El total_amount original se mantiene como fuente de verdad
            'number_of_installments' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'amounts' => 'required|array',
            'amounts.*' => 'required|numeric|min:0',
            'due_dates' => 'required|array',
            'due_dates.*' => 'required|date',
        ]);

        if (count($validated['amounts']) != $validated['number_of_installments']) {
            return back()->with('error', 'El número de cuotas no coincide con los montos enviados.');
        }
        
        // No se recalcula el total, se usa el 'total_amount' enviado por el usuario
        // y la advertencia se maneja en el frontend.
        
        $exists = \App\Models\PaymentPlan::where('lot_id', $lot->id)->where('service_id', $validated['service_id'])->exists();
        if ($exists) {
            return back()->with('error', 'Ya existe un plan de pago para este lote y servicio.');
        }

        try {
            DB::beginTransaction();

            // Crear el plan de pago con el total_amount original
            $paymentPlan = $lot->paymentPlans()->create([
                'service_id' => $validated['service_id'],
                'total_amount' => $validated['total_amount'],
                'number_of_installments' => $validated['number_of_installments'],
                'start_date' => $validated['start_date'],
            ]);

            foreach ($validated['amounts'] as $index => $amount) {
                $paymentPlan->installments()->create([
                    'installment_number' => $index + 1,
                    'due_date' => $validated['due_dates'][$index],
                    'amount' => $amount,
                    // El base_amount aquí es informativo, el 'amount' es el principal
                    'base_amount' => round($validated['total_amount'] / $validated['number_of_installments'], 2),
                ]);
            }

            DB::commit();

            Artisan::call('installments:update-status');
            
            return redirect()->route('lots.edit', $lot)->with('success', 'Plan de pago creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('lots.edit', $lot)->with('error', 'Error al crear el plan: ' . $e->getMessage());
        }
    }

    public function destroy(\App\Models\PaymentPlan $plan)
    {
        // Primero, verifica si el plan tiene transacciones asociadas.
        $hasTransactions = $plan->installments()->whereHas('transactions')->exists();

        // Si NO tiene transacciones, cualquier usuario puede eliminarlo.
        if (!$hasTransactions) {
            $plan->delete();
            return back()->with('success', 'Plan de pago eliminado exitosamente.');
        }

        // Si SÍ tiene transacciones, solo se puede eliminar si el usuario tiene permiso para forzarlo.
        if ($hasTransactions && auth()->user()->can('forceDelete', $plan)) {
            $plan->delete();
            return back()->with('success', 'Plan de pago y su historial han sido eliminados forzosamente.');
        }

        // Si llega aquí, es porque tiene transacciones y el usuario no es admin.
        return back()->with('error', 'No se puede eliminar: el plan tiene pagos registrados. Solo un administrador puede forzar esta acción.');
    }

    public function generateInstallments(PaymentPlan $paymentPlan)
    {
        $installments = [];
        $baseAmount = round($paymentPlan->total_amount / $paymentPlan->number_of_installments, 2);
        $startDate = Carbon::parse($paymentPlan->start_date);
        
        // Distribuir la diferencia por el redondeo en la primera cuota
        $totalCalculated = $baseAmount * $paymentPlan->number_of_installments;
        $difference = $paymentPlan->total_amount - $totalCalculated;

        for ($i = 1; $i <= $paymentPlan->number_of_installments; $i++) {
            $currentAmount = $baseAmount;
            if ($i === 1) {
                $currentAmount += $difference;
            }

            $installments[] = [
                'payment_plan_id' => $paymentPlan->id,
                'installment_number' => $i,
                'due_date' => $startDate->copy()->addMonths($i - 1)->toDateString(),
                'base_amount' => $currentAmount,
                'amount' => $currentAmount, // <-- AÑADIR ESTA LÍNEA
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Inserción masiva para eficiencia
        DB::table('installments')->insert($installments);
    }
}