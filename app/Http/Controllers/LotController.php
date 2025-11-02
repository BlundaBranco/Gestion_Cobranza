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
        $query = Lot::with('client');

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where('identifier', 'like', $searchTerm)
                ->orWhereHas('client', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm);
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
        return view('lots.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'block_number' => 'required|string|max:255', // Nueva validación
            'lot_number' => 'required|string|max:255',   // Nueva validación
            'client_id' => 'nullable|exists:clients,id',
            'service_id' => 'required|exists:services,id',
            'total_amount' => 'required|numeric|min:0',
            'number_of_installments' => 'required|integer|min:1',
            'start_date' => 'required|date',
        ]);

        // Validar unicidad combinada
        $exists = Lot::where('block_number', $validated['block_number'])
                    ->where('lot_number', $validated['lot_number'])
                    ->exists();
        if ($exists) {
            return back()->withErrors(['lot_number' => 'Este número de lote ya existe para la manzana especificada.'])->withInput();
        }
        
        try {
            DB::beginTransaction();

            $lot = Lot::create([
                'block_number' => $validated['block_number'], // Nuevo campo
                'lot_number' => $validated['lot_number'],     // Nuevo campo
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
            // Los campos 'client_id' y 'total_price' se envían ocultos y no necesitan validación estricta aquí
        ]);

        $lot->update($validated);

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