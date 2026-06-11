<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CreditBalanceMovement;
use App\Models\Installment;
use App\Models\Lot;
use App\Models\Transaction;
use App\Models\Owner;
use App\Models\OwnerSequence;
use App\Exports\TransactionHistoryExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::withTrashed()->with(['client', 'owner', 'installments.paymentPlan']);

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where('folio_number', 'like', $searchTerm)
                ->orWhereHas('client', function($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm);
                });
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        $transactions = $query->latest()->paginate(15)->withQueryString();
        $owners = Owner::orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'owners'));
    }

    public function export(Request $request)
    {
        $filename = 'historial-pagos-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(
            new TransactionHistoryExport($request->search, $request->owner_id),
            $filename
        );
    }

    public function update(Request $request, Transaction $transaction)
        {
            $validated = $request->validate([
                'payment_date' => 'required|date',
            ]);

            $transaction->update($validated);

            return back()->with('success', 'Fecha de pago actualizada correctamente.');
        }
        
    public function destroy(Transaction $transaction)
    {
        try {
            DB::transaction(function () use ($transaction) {
                // 1. Revertir movimientos de saldo a favor si la transacción los generó
                $transaction->loadMissing('creditBalanceMovements.client');
                foreach ($transaction->creditBalanceMovements as $movement) {
                    $client = $movement->client;
                    if (! $client) {
                        continue;
                    }

                    if ($movement->type === 'added') {
                        // La transacción había sumado saldo al cliente — al cancelar, restarlo.
                        // Si el cliente ya gastó ese crédito, no se puede revertir sin dejar saldo negativo.
                        if ((float) $client->credit_balance + 0.005 < (float) $movement->amount) {
                            throw ValidationException::withMessages([
                                'cancellation' => 'No se puede cancelar: el saldo a favor generado por este pago ya fue parcialmente o totalmente utilizado por el cliente en pagos posteriores.',
                            ]);
                        }
                        $client->credit_balance = (float) $client->credit_balance - (float) $movement->amount;
                    } else {
                        // Movimiento "applied" — su amount es negativo. Al revertir, devolver al cliente.
                        $client->credit_balance = (float) $client->credit_balance + abs((float) $movement->amount);
                    }
                    $client->save();
                    $movement->delete();
                }

                // 2. Revertir el estado de las cuotas y poner amount_applied a 0 en pivot
                foreach ($transaction->installments as $installment) {
                    if ($installment->status === 'pagada') {
                        $installment->status = $installment->due_date < now() ? 'vencida' : 'pendiente';
                        $installment->save();
                    }
                    // Preservar relación pero anular el monto aplicado (mantiene auditoría del concepto)
                    $transaction->installments()->updateExistingPivot($installment->id, ['amount_applied' => 0]);
                }

                // 3. Marcar como cancelada (soft delete + audit)
                $transaction->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                ]);
                $transaction->delete();
            });
        } catch (ValidationException $e) {
            return back()->with('error', $e->errors()['cancellation'][0] ?? 'No se pudo cancelar la transacción.');
        }

        return back()->with('success', 'Transacción cancelada y estados revertidos correctamente.');
    }


    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get();
        $lots = Lot::with('owner')->orderBy('block_number')->orderBy('lot_number')->get();
        $selectedClientId = $request->query('client_id');
        $selectedInstallmentId = $request->query('installment_id');

        return view('transactions.create', compact('clients', 'lots', 'selectedClientId', 'selectedInstallmentId'));
    }

    public function store(Request $request)
    {
        $isExtra = $request->boolean('is_extra');

        if ($isExtra) {
            return $this->storeExtra($request);
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount_paid' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
            'payment_method' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Transaction::PAYMENT_METHODS))],
            'installments' => 'required|array',
            'installments.*' => 'exists:installments,id',
            'apply_credit' => 'nullable|boolean',
        ]);

        $cashAmount = floatval($validated['amount_paid']);
        $applyCredit = $request->boolean('apply_credit');
        $selectedInstallments = Installment::with(['transactions', 'paymentPlan.service'])->find($validated['installments']);
        $client = Client::findOrFail($validated['client_id']);
        $creditAvailable = $applyCredit ? (float) $client->credit_balance : 0;

        // --- EMISOR DE FOLIO POR SERVICIO ---
        // Un servicio puede tener emisor de folios propio (services.billing_owner_id),
        // p. ej. ELECTRIFICACIÓN. No se mezclan en un mismo recibo cuotas de un servicio
        // con emisor propio junto con cuotas de otro emisor (otro servicio o un socio):
        // cada uno lleva su propia numeración, así que va en cobros separados.
        $billingOwnerIds = $selectedInstallments
            ->map(fn ($i) => $i->paymentPlan?->service?->billing_owner_id)
            ->unique()
            ->values();

        $ownBilling = $billingOwnerIds->filter(fn ($v) => ! is_null($v));
        $hasNormal  = $billingOwnerIds->contains(null);

        if ($ownBilling->isNotEmpty() && ($hasNormal || $ownBilling->count() > 1)) {
            return back()
                ->with('error', 'No se puede cobrar en un mismo recibo cuotas de un servicio con numeración propia (ej. Electrificación) junto con otras. Cobrá ese servicio por separado para que tome su propio folio.')
                ->withInput();
        }

        // Emisor del folio: el propio del servicio si lo tiene; si no, el socio del lote (más abajo).
        $billingOwnerId = $ownBilling->first();

        // Suma efectiva a aplicar (efectivo + crédito disponible si fue marcado).
        // Si el cliente sólo aplica crédito sin efectivo, $cashAmount = 0 es válido.
        if (($cashAmount + $creditAvailable) < 0.01) {
            return back()
                ->with('error', 'El monto total a aplicar (efectivo + saldo a favor) debe ser mayor a cero.')
                ->withInput();
        }

        $amountToApply = $cashAmount + $creditAvailable;
        $transaction = null;

        try {
            DB::beginTransaction();

            // amount_paid registra SOLO el efectivo recibido (ingreso real de caja).
            // El crédito aplicado se rastrea por separado vía CreditBalanceMovement.
            $transaction = $client->transactions()->create([
                'amount_paid' => $cashAmount,
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
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

            // --- LÓGICA DE FOLIO MULTI-EMISOR ---
            // Debe ejecutarse DENTRO de la transacción DB para que lockForUpdate sea efectivo.
            if ($billingOwnerId) {
                // Servicio con emisor propio (ej. ELECTRIFICACIÓN): folio de su secuencia.
                $ownerId = $billingOwnerId;
            } else {
                // Comportamiento normal: folio del socio dueño del lote de la primera cuota.
                $ownerId = null;
                $firstInstallment = $installmentsToProcess->first();
                if ($firstInstallment) {
                    $lot = $firstInstallment->paymentPlan->lot ?? null;
                    $ownerId = $lot?->owner_id;
                }
            }

            if ($ownerId) {
                // Secuencia independiente por Owner. lockForUpdate() en getNextValue
                // garantiza atomicidad únicamente cuando se llama dentro de esta transacción.
                $nextValue = OwnerSequence::getNextValue($ownerId);
                $transaction->folio_number = 'FOLIO-' . str_pad($nextValue, 6, '0', STR_PAD_LEFT);
            } else {
                // Fallback para lotes sin owner: usar el ID de la transacción
                $transaction->folio_number = 'FOLIO-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT);
            }
            // --- FIN LÓGICA DE FOLIO MULTI-EMISOR ---

            // Guardar el owner al momento de creación para mantener trazabilidad histórica
            $transaction->owner_id = $ownerId;
            $transaction->save();

            // --- GESTIÓN DE SALDO A FAVOR ---
            // El crédito se consume PRIMERO al aplicarse a cuotas; el cash cubre lo restante.
            // Sobrante al final del loop = excedente de cash (porque el crédito ya estaba "comprometido").
            $totalConsumed = ($cashAmount + $creditAvailable) - $amountToApply;
            $creditConsumed = min($creditAvailable, $totalConsumed);
            $cashExcess = max(0, $cashAmount - ($totalConsumed - $creditConsumed));

            if ($creditConsumed > 0.005) {
                $client->credit_balance = (float) $client->credit_balance - $creditConsumed;
                CreditBalanceMovement::create([
                    'client_id'      => $client->id,
                    'amount'         => -$creditConsumed,
                    'type'           => 'applied',
                    'transaction_id' => $transaction->id,
                    'notes'          => 'Saldo a favor aplicado al pago ' . $transaction->folio_number,
                    'created_by'     => auth()->id(),
                ]);
            }

            if ($cashExcess > 0.005) {
                $client->credit_balance = (float) $client->credit_balance + $cashExcess;
                CreditBalanceMovement::create([
                    'client_id'      => $client->id,
                    'amount'         => $cashExcess,
                    'type'           => 'added',
                    'transaction_id' => $transaction->id,
                    'notes'          => 'Excedente del pago ' . $transaction->folio_number . ' registrado como saldo a favor',
                    'created_by'     => auth()->id(),
                ]);
            }

            if ($creditConsumed > 0.005 || $cashExcess > 0.005) {
                $client->save();
            }
            // --- FIN GESTIÓN DE SALDO A FAVOR ---

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar el pago: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Pago registrado exitosamente.')
            ->with('new_transaction_id', $transaction->id);
    }

    protected function storeExtra(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'lot_id' => 'required|exists:lots,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'required|string|max:500',
            'currency' => 'nullable|in:MXN,USD',
            'payment_method' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Transaction::PAYMENT_METHODS))],
        ]);

        $client = Client::findOrFail($validated['client_id']);
        $lot = Lot::findOrFail($validated['lot_id']);
        $ownerId = $lot->owner_id;

        if (!$ownerId) {
            return back()
                ->with('error', 'El lote seleccionado no tiene un socio asignado — no se puede generar folio.')
                ->withInput();
        }

        $transaction = null;

        try {
            DB::beginTransaction();

            $transaction = $client->transactions()->create([
                'amount_paid' => floatval($validated['amount_paid']),
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'],
                'user_id' => auth()->id(),
                'type' => 'extra',
                'owner_id' => $ownerId,
                'currency' => $validated['currency'] ?? 'MXN',
                'payment_method' => $validated['payment_method'] ?? null,
            ]);

            $nextValue = OwnerSequence::getNextValue($ownerId);
            $transaction->folio_number = 'FOLIO-' . str_pad($nextValue, 6, '0', STR_PAD_LEFT);
            $transaction->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar el cobro extra: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Cobro extra registrado exitosamente.')
            ->with('new_transaction_id', $transaction->id);
    }

    public function showPdf($id)
    {
        $transaction = Transaction::withTrashed()->findOrFail($id);
        $transaction->load(['client', 'user', 'owner', 'installments.paymentPlan.lot', 'installments.paymentPlan.service']);

        // Impresión 1 = original; de la segunda en adelante el recibo sale marcado REIMPRESIÓN.
        // withTrashed en el update: los recibos cancelados también se reimprimen y deben contar.
        $isReprint = $transaction->print_count > 0;
        Transaction::withTrashed()->whereKey($transaction->id)
            ->increment('print_count', 1, ['last_printed_at' => now()]);

        $view = $transaction->type === 'extra' ? 'transactions.pdf_extra' : 'transactions.pdf';
        $pdf = PDF::loadView($view, compact('transaction', 'isReprint'));

        return $pdf->stream('recibo-' . $transaction->folio_number . '.pdf');
    }
}