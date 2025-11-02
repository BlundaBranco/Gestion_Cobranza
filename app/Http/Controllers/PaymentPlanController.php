<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\PaymentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentPlanController extends Controller
{
    public function store(Request $request, Lot $lot)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'total_amount' => 'required|numeric|min:0',
            'number_of_installments' => 'required|integer|min:1',
            'start_date' => 'required|date',
        ]);

        // Evitar planes de pago duplicados para el mismo lote y servicio
        $exists = PaymentPlan::where('lot_id', $lot->id)
            ->where('service_id', $validated['service_id'])
            ->exists();
            
        if ($exists) {
            return back()->withErrors(['service_id' => 'Ya existe un plan de pago para este lote y servicio.']);
        }

        try {
            DB::beginTransaction();

            $paymentPlan = $lot->paymentPlans()->create($validated);

            $this->generateInstallments($paymentPlan);

            DB::commit();

            return redirect()->route('lots.edit', $lot)->with('success', 'Plan de pago y cuotas generados exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('lots.edit', $lot)->with('error', 'Error al generar el plan de pago: ' . $e->getMessage());
        }
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
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Inserción masiva para eficiencia
        DB::table('installments')->insert($installments);
    }
}