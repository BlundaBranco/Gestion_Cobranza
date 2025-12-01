<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PaymentPlanController;

class LotController extends Controller
{
    public function index(Request $request)
    {
        $query = Lot::with(['client', 'owner']);

        if ($request->filled('search')) {
            $search = $request->search;
            if (preg_match('/^(?:manzana|mz|m)\s*(\d+),?\s*(?:lote|l)?\s*(\d+)?$/i', $search, $matches)) {
                $block = $matches[1];
                $lotNum = $matches[2] ?? null;
                $query->where('block_number', $block);
                if ($lotNum) {
                    $query->where('lot_number', $lotNum);
                }
            } elseif (preg_match('/^(?:manzana|mz|m)\s*(\d+)$/i', $search, $matches)) {
                $block = $matches[1];
                $query->where('block_number', $block);
            } else {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('client', fn($subQ) => $subQ->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('owner', fn($subQ) => $subQ->where('name', 'like', "%{$search}%"));
                });
            }
        }
        
        $lots = $query->latest()->paginate(9)->withQueryString();

        $lots->getCollection()->transform(function ($lot) {
            $totalLotDebt = 0;
            $paymentPlans = $lot->paymentPlans()->with(['installments.transactions', 'service'])->get();

            if ($paymentPlans->isNotEmpty()) {
                $lot->payment_plans_summary = $paymentPlans->map(function ($plan) use (&$totalLotDebt) {
                    $planDebt = $plan->installments->reduce(function ($carry, $installment) {
                        $totalDue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                        $totalPaid = $installment->transactions->sum('pivot.amount_applied');
                        return $carry + max(0, $totalDue - $totalPaid);
                    }, 0);

                    $totalLotDebt += $planDebt;

                    return [
                        'service_name' => $plan->service->name,
                        'debt' => $planDebt,
                        'currency' => $plan->currency,
                    ];
                });
            } else {
                $lot->payment_plans_summary = collect(); // Asignar una colección vacía
            }
                
            $lot->total_debt = $totalLotDebt;
            return $lot;
        });
        
        return view('lots.index', compact('lots'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $owners = \App\Models\Owner::orderBy('name')->get();
        return view('lots.create', compact('clients', 'owners'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => 'nullable|exists:owners,id',
            'block_number' => 'required|string|max:255',
            'lot_number' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        if (Lot::where('block_number', $validated['block_number'])->where('lot_number', $validated['lot_number'])->exists()) {
            return back()->withErrors(['lot_number' => 'Este lote ya existe para la manzana especificada.'])->withInput();
        }
        
        Lot::create([
            'owner_id' => $validated['owner_id'],
            'block_number' => $validated['block_number'],
            'lot_number' => $validated['lot_number'],
            'client_id' => $validated['client_id'],
            'status' => $validated['client_id'] ? 'vendido' : 'disponible',
            'total_price' => 0,
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
            'notes' => 'nullable|string',
        ]);

        $lot->identifier = $validated['identifier'];
        $lot->status = $validated['status'];
        $lot->notes = $validated['notes'];

        if ($request->has('owner_id')) {
            $lot->owner_id = $validated['owner_id'];
        }

        $lot->save();

        return redirect()->route('lots.edit', $lot)->with('success', 'Lote actualizado exitosamente.');
    }

    public function destroy(Lot $lot)
    {
        if ($lot->paymentPlans()->exists()) {
            if (auth()->user()->can('forceDelete', $lot)) {
                $lot->delete();
                return redirect()->route('lots.index')->with('success', 'Lote y su historial han sido eliminados forzosamente.');
            }
            return back()->with('error', 'No se puede eliminar: el lote tiene planes de pago. Solo un administrador puede forzar esta acción.');
        }

        $lot->delete();
        return redirect()->route('lots.index')->with('success', 'Lote eliminado exitosamente.');
    }
}