<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Exports\ClientAccountExport;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query()->withCount('lots');

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                ->orWhere('email', 'like', $searchTerm)
                ->orWhere('phone', 'like', $searchTerm);
            });
        }

        $clients = $query->latest()->paginate(10)->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function export(Client $client)
    {
        return Excel::download(new ClientAccountExport($client), 'estado_cuenta_' . $client->id . '.xlsx');
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        Client::create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Cliente creado exitosamente.');
    }

    public function show(\App\Models\Client $client)
    {
        // Cargar todas las relaciones necesarias
        $client->load([
            'lots.paymentPlans.service',
            'lots.paymentPlans.installments.transactions',
            'transactions',
            'documents'
        ]);

        // Inicializar el array de estadísticas con el desglose
        $stats = [
            'servicesCount' => $client->lots->count(),
            'pendingInstallmentsCount' => 0,
            
            // Desglose de Deuda
            'debt_capital' => 0,
            'debt_interest' => 0,
            'total_debt' => 0,

            // Desglose de Pagado
            'paid_capital' => 0,
            'paid_interest' => 0,
            'total_paid' => 0,
        ];

        foreach ($client->lots as $lot) {
            foreach ($lot->paymentPlans as $plan) {
                foreach ($plan->installments as $installment) {
                    // 1. Obtener valores base
                    $totalPaidForInstallment = $installment->transactions->sum('pivot.amount_applied');
                    $interestAmount = $installment->interest_amount;
                    // Usar el campo editable 'amount' si existe, si no, el original 'base_amount'
                    $baseAmount = $installment->amount ?? $installment->base_amount;
                    $totalAmount = $baseAmount + $interestAmount;

                    // 2. Calcular desglose de LO PAGADO
                    // Asumimos que el pago cubre primero el interés y luego el capital
                    $paidInterest = min($totalPaidForInstallment, $interestAmount);
                    $paidCapital = $totalPaidForInstallment - $paidInterest;

                    // 3. Calcular desglose de LA DEUDA
                    $remainingTotal = $totalAmount - $totalPaidForInstallment;
                    
                    if ($remainingTotal > 0.005) {
                        $stats['pendingInstallmentsCount']++;
                        
                        // Lo que falta de interés es el total de interés menos lo que ya se cubrió
                        $remainingInterest = $interestAmount - $paidInterest;
                        // Lo que falta de capital es el total de capital menos lo que ya se cubrió
                        $remainingCapital = $baseAmount - $paidCapital;

                        // Asegurarse de no sumar negativos por errores de redondeo
                        $stats['debt_interest'] += max(0, $remainingInterest);
                        $stats['debt_capital'] += max(0, $remainingCapital);
                        $stats['total_debt'] += $remainingTotal;
                    }

                    // 4. Acumular totales pagados en las estadísticas generales
                    $stats['paid_interest'] += $paidInterest;
                    $stats['paid_capital'] += $paidCapital;
                    $stats['total_paid'] += $totalPaidForInstallment;
                }
            }
        }

        // Obtener transacciones recientes para la vista
        $recentTransactions = $client->transactions()->latest()->take(5)->get();

        return view('clients.show', compact('client', 'stats', 'recentTransactions'));
    }
    
    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:clients,email,' . $client->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Usar update() directamente con los datos validados asegura que se guarden todos los campos.
        $client->update($validated);

        return redirect()->route('clients.edit', $client)->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Client $client)
    {
        if ($client->lots()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un cliente con lotes asociados.');
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }
}