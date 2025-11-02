<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query()->withCount('lots'); // withCount es eficiente

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

    public function show(Client $client)
    {
        // Cargar relaciones necesarias
        $client->load([
            'lots.paymentPlans.service', 
            'lots.paymentPlans.installments.transactions',
            'transactions'
        ]);

        $totalDebt = 0;
        $totalPaid = 0;
        $pendingInstallmentsCount = 0;

        foreach ($client->lots as $lot) {
            foreach ($lot->paymentPlans as $plan) {
                foreach ($plan->installments as $installment) {
                    $totalDueForInstallment = $installment->base_amount + $installment->interest_amount;
                    $paidForInstallment = $installment->transactions->sum('pivot.amount_applied');
                    $remaining = $totalDueForInstallment - $paidForInstallment;

                    if ($remaining > 0.005) {
                        $totalDebt += $remaining;
                        $pendingInstallmentsCount++;
                    }
                    $totalPaid += $paidForInstallment;
                }
            }
        }

        $stats = [
            'servicesCount' => $client->lots->count(),
            'totalDebt' => $totalDebt,
            'totalPaid' => $totalPaid,
            'pendingInstallmentsCount' => $pendingInstallmentsCount,
        ];

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
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Client $client)
    {
        // Verificar que no tenga lotes asociados
        if ($client->lots()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un cliente con lotes asociados.');
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }
}