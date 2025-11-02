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
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('identifier', 'like', $searchTerm)
                ->orWhereHas('client', function ($subQ) use ($searchTerm) {
                    $subQ->where('name', 'like', $searchTerm);
                })
                ->orWhereHas('owner', function ($subQ) use ($searchTerm) { // <-- LÓGICA AÑADIDA
                    $subQ->where('name', 'like', $searchTerm);
                });
            });
        }
        
        $lots = $query->latest()->paginate(9)->withQueryString();
        
        $lots->getCollection()->transform(function ($lot) {
            $lot->total_debt = 0;
            $lot->payment_plans_summary = $lot->paymentPlans()
                ->with(['installments.transactions', 'service'])
                ->get()
                ->map(function ($plan) use (&$lot) {
                    $totalDue = $plan->installments->sum(fn($inst) => $inst->base_amount + $inst->interest_amount);
                    $totalPaid = $plan->installments->sum(fn($inst) => $inst->transactions->sum('pivot.amount_applied'));
                    $debt = $totalDue - $totalPaid;
                    $lot->total_debt += $debt;
                    
                    return [
                        'service_name' => $plan->service->name,
                        'debt' => $debt > 0 ? $debt : 0,
                    ];
                });
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
            'owner_id' => 'required|exists:owners,id',
            'block_number' => 'required|string|max:255',
            'lot_number' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'service_id' => 'required|exists:services,id',
            'total_amount' => 'required|numeric|min:0',
            'number_of_installments' => 'required|integer|min:1',
            'start_date' => 'required|date',
        ]);

        // Validar unicidad combinada de manzana y lote para el mismo socio
        $exists = Lot::where('owner_id', $validated['owner_id'])
                    ->where('block_number', $validated['block_number'])
                    ->where('lot_number', $validated['lot_number'])
                    ->exists();

        if ($exists) {
            return back()->withErrors(['lot_number' => 'Este número de lote ya existe para la manzana y socio especificados.'])->withInput();
        }
        
        try {
            DB::beginTransaction();

            $lot = Lot::create([
                'owner_id' => $validated['owner_id'],
                'block_number' => $validated['block_number'],
                'lot_number' => $validated['lot_number'],
                'client_id' => $validated['client_id'],
                'total_price' => $validated['total_amount'],
                'status' => $validated['client_id'] ? 'vendido' : 'disponible',
            ]);

            $paymentPlan = $lot->paymentPlans()->create([
                'service_id' => $validated['service_id'],
                'total_amount' => $validated['total_amount'],
                'number_of_installments' => $validated['number_of_installments'],
                'start_date' => $validated['start_date'],
            ]);
            
            $paymentPlanController = new PaymentPlanController();
            $paymentPlanController->generateInstallments($paymentPlan);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear el lote: ' . $e->getMessage())->withInput();
        }
        
        return redirect()->route('lots.index')->with('success', 'Lote y plan de pago creados exitosamente.');
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
        if ($lot->paymentPlans()->exists()) {
            return back()->with('error', 'No se puede eliminar un lote con planes de pago asociados.');
        }

        $lot->delete();

        return redirect()->route('lots.index')->with('success', 'Lote eliminado exitosamente.');
    }
}