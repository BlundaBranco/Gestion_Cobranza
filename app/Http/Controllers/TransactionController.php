<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Installment;
use App\Models\Transaction;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['client', 'installments.paymentPlan.lot.owner']);

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where('folio_number', 'like', $searchTerm)
                ->orWhereHas('client', function($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm);
                });
        }

        if ($request->filled('owner_id')) {
            $ownerId = $request->owner_id;
            $query->whereHas('installments.paymentPlan.lot', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            });
        }

        $transactions = $query->latest()->paginate(15)->withQueryString();
        $owners = Owner::orderBy('name')->get();
        
        return view('transactions.index', compact('transactions', 'owners'));
    }

    public function update(Request $request, Transaction $transaction)
        {
            $validated = $request->validate([
                'payment_date' => 'required|date',
            ]);

            $transaction->update($validated);

            return back()->with('success', 'Fecha de pago actualizada correctamente.');
        }

    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get();
        $selectedClientId = $request->query('client_id');
        $selectedInstallmentId = $request->query('installment_id');

        return view('transactions.create', compact('clients', 'selectedClientId', 'selectedInstallmentId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
            'installments' => 'required|array',
            'installments.*' => 'exists:installments,id',
        ]);

        $amountPaid = floatval($validated['amount_paid']);
        $selectedInstallments = Installment::with('transactions')->find($validated['installments']);
        
        // --- CÁLCULO DE DEUDA ACTUALIZADO ---
        $totalDueForSelected = $selectedInstallments->reduce(function ($carry, $installment) {
            // Usa el campo 'amount' si existe, si no, el 'base_amount'
            $totalOwed = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
            $totalPaid = $installment->transactions->sum('pivot.amount_applied');
            return $carry + ($totalOwed - $totalPaid);
        }, 0);
        // --- FIN DE ACTUALIZACIÓN ---

        if ($amountPaid > round($totalDueForSelected, 2) + 0.001) {
            throw ValidationException::withMessages([
                'amount_paid' => 'El monto a pagar no puede ser mayor que el adeudo de las cuotas seleccionadas (Total: $' . number_format($totalDueForSelected, 2) . ').'
            ]);
        }

        $client = Client::findOrFail($validated['client_id']);
        $amountToApply = $amountPaid;
        $transaction = null;

        try {
            DB::beginTransaction();

            $transaction = $client->transactions()->create([
                'amount_paid' => $amountToApply,
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'],
                'user_id' => auth()->id(),
            ]);

            $installmentsToProcess = $selectedInstallments->sortBy('due_date');

            foreach ($installmentsToProcess as $installment) {
                if ($amountToApply <= 0) break;

                // --- LÓGICA DE PAGO ACTUALIZADA ---
                $paidSoFar = $installment->transactions->sum('pivot.amount_applied');
                $totalValue = ($installment->amount ?? $installment->base_amount) + $installment->interest_amount;
                $remainingBalance = $totalValue - $paidSoFar;
                // --- FIN DE ACTUALIZACIÓN ---

                $amountForThisInstallment = min($amountToApply, $remainingBalance);

                if ($amountForThisInstallment > 0) {
                    $transaction->installments()->attach($installment->id, ['amount_applied' => $amountForThisInstallment]);
                }

                $newTotalPaid = $paidSoFar + $amountForThisInstallment;
                if ($newTotalPaid >= $totalValue - 0.001) {
                    $installment->status = 'pagada';
                    $installment->save();
                }

                $amountToApply -= $amountForThisInstallment;
            }
            
            $transaction->folio_number = 'FOLIO-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT);
            $transaction->save();

            DB::commit();

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el pago: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Pago registrado exitosamente.')
            ->with('new_transaction_id', $transaction->id);
    }

    public function showPdf(Transaction $transaction)
    {
        $transaction->load(['client', 'user', 'installments.paymentPlan.lot', 'installments.paymentPlan.service']);
        
        $pdf = PDF::loadView('transactions.pdf', compact('transaction'));
        
        return $pdf->stream('recibo-' . $transaction->folio_number . '.pdf');
    }
}