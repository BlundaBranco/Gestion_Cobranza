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
            'phone_label' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'additional_phones' => 'nullable|string|max:500',
        ]);

        Client::create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Cliente creado exitosamente.');
    }

    public function show(\App\Models\Client $client)
    {
        $client->load([
            'lots.paymentPlans.service',
            'lots.paymentPlans.installments.transactions',
            'documents'
        ]);
        
        $statsByCurrency = [];
        $pendingInstallmentsCount = 0;
        
        foreach ($client->lots->flatMap->paymentPlans as $plan) {
            $currency = $plan->currency;

            if (!isset($statsByCurrency[$currency])) {
                $statsByCurrency[$currency] = [
                    'total_debt' => 0,
                    'debt_capital' => 0,
                    'debt_interest' => 0,
                    'total_paid' => 0,
                    'paid_capital' => 0,
                    'paid_interest' => 0,
                    'months_overdue' => 0, // <-- NUEVO: Contador de meses en mora
                ];
            }

            foreach ($plan->installments as $installment) {
                $totalPaidForInstallment = $installment->transactions->sum('pivot.amount_applied');
                $interestAmount = $installment->interest_amount;
                $baseAmount = $installment->amount ?? $installment->base_amount;
                $totalAmount = $baseAmount + $interestAmount;
                
                $remainingTotal = $totalAmount - $totalPaidForInstallment;

                $paidInterest = min($totalPaidForInstallment, $interestAmount);
                $paidCapital = $totalPaidForInstallment - $paidInterest;

                $statsByCurrency[$currency]['total_paid'] += $totalPaidForInstallment;
                $statsByCurrency[$currency]['paid_capital'] += $paidCapital;
                $statsByCurrency[$currency]['paid_interest'] += $paidInterest;

                if ($remainingTotal > 0.01) {
                    $pendingInstallmentsCount++;
                    
                    $remainingInterest = $interestAmount - $paidInterest;
                    $remainingCapital = $baseAmount - $paidCapital;
                    
                    $statsByCurrency[$currency]['total_debt'] += $remainingTotal;
                    $statsByCurrency[$currency]['debt_capital'] += max(0, $remainingCapital);
                    $statsByCurrency[$currency]['debt_interest'] += max(0, $remainingInterest);

                    // NUEVO: Si la cuota está vencida y tiene saldo, sumar al contador de meses
                    if ($installment->status === 'vencida') {
                        $statsByCurrency[$currency]['months_overdue']++;
                    }
                }
            }
        }
        
        return view('clients.show', compact('client', 'pendingInstallmentsCount', 'statsByCurrency'));
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
            'phone_label' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'additional_phones' => 'nullable|string|max:500',
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