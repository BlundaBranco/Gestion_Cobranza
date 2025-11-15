<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PaymentPlanController; // Asegúrate que esta línea esté presente

class LotController extends Controller
{
    public function index(Request $request)
    {
        $query = Lot::with(['client', 'owner']);

        if ($request->filled('search')) {
            $search = $request->search;

            // Lógica mejorada para buscar por Manzana y Lote
            // Busca patrones como "manzana 10 lote 5", "m 10 l 5", "10 5"
            if (preg_match('/^(?:manzana|mz|m)\s*(\d+),?\s*(?:lote|l)?\s*(\d+)?$/i', $search, $matches)) {
                $block = $matches[1];
                $lot = $matches[2] ?? null;

                $query->where('block_number', $block);
                if ($lot) {
                    $query->where('lot_number', $lot);
                }
            } 
            // Busca patrones como "manzana 10", "m 10"
            elseif (preg_match('/^(?:manzana|mz|m)\s*(\d+)$/i', $search, $matches)) {
                $block = $matches[1];
                $query->where('block_number', $block);
            }
            // Si no coincide, busca por nombre de cliente o socio
            else {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('client', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('owner', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });
                });
            }
        }
        
        $lots = $query->latest()->paginate(9)->withQueryString();
        
        $lots->getCollection()->transform(function ($lot) {
            $totalLotDebt = 0;
            $lot->payment_plans_summary = $lot->paymentPlans()
                ->with(['installments.transactions', 'service'])
                ->get()
                ->map(function ($plan) use (&$totalLotDebt) {
                    
                    $planDebt = $plan->installments->reduce(function ($carry, $installment) {
                        $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                        $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                        $remaining = $totalDue - $totalPaid;
                        
                        return $carry + ($remaining > 0 ? $remaining : 0);
                    }, 0);

                    $totalLotDebt += $planDebt;
                    
                    return [
                        'service_name' => $plan->service->name,
                        'debt' => $planDebt,
                    ];
                });
                
            $lot->total_debt = $totalLotDebt;
            return $lot;
        });

        return view('lots.index', compact('lots'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $owners = \App\Models\Owner::orderBy('name')->get(); // Cargar la lista de socios
        
        return view('lots.create', compact('clients', 'owners')); // Pasar socios a la vista
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => 'nullable|exists:owners,id',
            'block_number' => 'required|string|max:255',
            'lot_number' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        // Validar unicidad combinada
        if (Lot::where('block_number', $validated['block_number'])->where('lot_number', $validated['lot_number'])->exists()) {
            return back()->withErrors(['lot_number' => 'Este número de lote ya existe para la manzana especificada.'])->withInput();
        }
        
        // Crear el lote
        Lot::create([
            'owner_id' => $validated['owner_id'],
            'block_number' => $validated['block_number'],
            'lot_number' => $validated['lot_number'],
            'client_id' => $validated['client_id'],
            'status' => $validated['client_id'] ? 'vendido' : 'disponible',
            'total_price' => 0, // El precio ahora se define en el plan de pago
        ]);
        
        return redirect()->route('lots.index')->with('success', 'Lote creado exitosamente. Ahora puedes asignarle planes de pago.');
    }

    public function edit(Lot $lot)
    {
        $lot->load('ownershipHistory.previousClient', 'ownershipHistory.newClient');
        $clients = Client::orderBy('name')->get();
        return view('lots.edit', compact('lot', 'clients'));
    }

    public function update(Request $request, Lot $lot)
    {
        $validated = $request->validate([
            'identifier' => 'required|string|max:255|unique:lots,identifier,' . $lot->id,
            'status' => 'required|in:disponible,vendido,liquidado',
            'owner_id' => 'sometimes|nullable|exists:owners,id',
            
        ]);

        $lot->identifier = $validated['identifier'];
        $lot->status = $validated['status'];
        $lot->notes = $request->input('notes');

        if ($request->has('owner_id')) {
            $lot->owner_id = $validated['owner_id'];
        }

        // --- LÓGICA AÑADIDA ---
        // Si el lote estaba disponible y ahora tiene un cliente, marcarlo como vendido.
        // (Asumimos que el client_id viene del input oculto si ya existía).
        if ($lot->isDirty('client_id') && $request->input('client_id') && $lot->getOriginal('status') === 'disponible') {
            $lot->status = 'vendido';
        }
        // Lógica similar para la transferencia
        if ($lot->isDirty('client_id') && $request->input('client_id') && $lot->status === 'disponible') {
            $lot->status = 'vendido';
        }
        // --- FIN LÓGICA AÑADIDA ---

        $lot->save();

        return redirect()->route('lots.edit', $lot)->with('success', 'Lote actualizado exitosamente.');
    }

    public function destroy(Lot $lot)
    {
        // Restricción estándar: no se puede eliminar si tiene planes de pago
        if ($lot->paymentPlans()->exists()) {
            // PERO, si el usuario es admin, sí puede forzar la eliminación
            if (auth()->user()->can('forceDelete', $lot)) {
                $lot->delete(); // La eliminación en cascada se encargará del resto
                return redirect()->route('lots.index')->with('success', 'Lote y todo su historial han sido eliminados forzosamente.');
            }
            return back()->with('error', 'No se puede eliminar: el lote tiene planes de pago asociados. Solo un administrador puede forzar esta acción.');
        }

        $lot->delete();
        return redirect()->route('lots.index')->with('success', 'Lote eliminado exitosamente.');
    }
}