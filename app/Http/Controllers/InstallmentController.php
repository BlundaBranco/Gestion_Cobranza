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

}