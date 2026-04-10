<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\Owner;
use Illuminate\Http\Request;

class OwnerTransferController extends Controller
{
    public function transfer(Request $request, Lot $lot)
        {
            $validated = $request->validate([
                'new_owner_id' => 'required|exists:owners,id',
            ]);

            $newOwnerId = $validated['new_owner_id'];

            if ($lot->owner_id == $newOwnerId) {
                return back()->with('error', 'El lote ya pertenece a este socio.');
            }

            // Bloquear cambio si el lote ya tiene transacciones registradas
            $hasTransactions = $lot->paymentPlans()
                ->whereHas('installments.transactions')
                ->exists();

            if ($hasTransactions) {
                return back()->with('error', 'No se puede cambiar el socio de este lote porque ya tiene pagos registrados. Contacte al administrador si necesita hacer esta corrección.');
            }

            // Asignación directa y guardado explícito para evitar fallos de Mass Assignment
            $lot->owner_id = $newOwnerId;
            $lot->save();

            return redirect()->route('lots.edit', $lot)->with('success', 'Socio del lote actualizado exitosamente.');
        }
}