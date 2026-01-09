<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class InstallmentController extends Controller
{
    public function condoneInterest(Installment $installment)
    {
        $installment->update(['interest_amount' => 0]);

        return back()->with('success', 'Intereses condonados exitosamente.');
    }

    public function update(Request $request, Installment $installment)
        {
            $validated = $request->validate([
                'amount' => 'sometimes|required|numeric|min:0',
                'due_date' => 'sometimes|required|date',
            ]);

            $installment->update($validated);

            // Recalcular estados e intereses inmediatamente
            // Esto asegura que si cambias una fecha al pasado, se marque como vencida y calcule el 10% al instante.
            \Illuminate\Support\Facades\Artisan::call('installments:update-status');

            return back()->with('success', 'Cuota actualizada correctamente.');
        }

    public function updateInterest(Request $request, Installment $installment)
    {
        $validated = $request->validate([
            'interest_amount' => 'required|numeric|min:0'
        ]);

        $installment->update(['interest_amount' => $validated['interest_amount']]);
        
        // Recalcular estados por si el cambio de interés afecta el saldo total
        \Illuminate\Support\Facades\Artisan::call('installments:update-status');

        return back()->with('success', 'Interés actualizado correctamente.');
    }

    public function bulkCondone(Request $request)
    {
        $validated = $request->validate([
            'selected_installments' => 'required|array',
            'selected_installments.*' => 'exists:installments,id'
        ]);

        Installment::whereIn('id', $validated['selected_installments'])
            ->update(['interest_amount' => 0]);

        return back()->with('success', 'Intereses condonados para las cuotas seleccionadas.');
    }

    public function store(Request $request, \App\Models\PaymentPlan $plan)
        {
            $validated = $request->validate([
                'installment_number' => 'required|integer', // 0 para enganche extra, o el N° siguiente
                'due_date' => 'required|date',
                'amount' => 'required|numeric|min:0',
            ]);

            // 1. Crear la nueva cuota
            $plan->installments()->create([
                'installment_number' => $validated['installment_number'],
                'due_date' => $validated['due_date'],
                'amount' => $validated['amount'],
                'base_amount' => $validated['amount'],
                'status' => 'pendiente'
            ]);
            
            // 2. Actualizar los totales del Plan de Pago
            $plan->total_amount += $validated['amount']; // Sumar el dinero
            $plan->number_of_installments += 1;          // Sumar 1 a la cantidad de cuotas
            $plan->save();

            // 3. Ejecutar comando para verificar si la fecha ya pasó (vencida/intereses)
            \Illuminate\Support\Facades\Artisan::call('installments:update-status');

            return back()->with('success', 'Cuota adicional agregada correctamente.');
        }

}