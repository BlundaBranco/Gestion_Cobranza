<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotTransferController extends Controller
{
    public function transfer(Request $request, Lot $lot)
    {
        $validated = $request->validate([
            'new_client_id' => 'required|exists:clients,id',
            'transfer_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        
        $previousClientId = $lot->client_id;
        
        // No permitir transferir al mismo cliente.
        if ($validated['new_client_id'] == $previousClientId) {
            return back()->withErrors(['new_client_id' => 'El lote ya pertenece a este cliente.']);
        }
        
        try {
            DB::beginTransaction();

            // 1. Registrar el historial
            $lot->ownershipHistory()->create([
                'previous_client_id' => $previousClientId,
                'new_client_id' => $validated['new_client_id'],
                'transfer_date' => $validated['transfer_date'],
                'notes' => $validated['notes'],
            ]);

            // 2. Actualizar el propietario y el estado
            $updateData = ['client_id' => $validated['new_client_id']];
            if ($lot->status === 'disponible') {
                $updateData['status'] = 'vendido';
            }
            $lot->update($updateData);

            DB::commit();

            return redirect()->route('lots.edit', $lot)->with('success', 'Lote transferido exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al transferir el lote: ' . $e->getMessage());
        }
    }
}