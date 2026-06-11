<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Owner;
use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Exports\IncomeExport;
use App\Exports\OverdueInstallmentsExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{


    public function incomeReport(Request $request)
    {
        // Obtener parámetros con valores por defecto
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $ownerId = $request->input('owner_id');
        $paymentMethod = $request->input('payment_method');

        // Parámetros de Rango de Folio
        $folioFrom = $request->input('folio_from');
        $folioTo = $request->input('folio_to');

        $query = \App\Models\Transaction::withTrashed()->with(['client', 'owner', 'user', 'installments.paymentPlan.lot.owner']);

        // LOGICA DE FILTRADO

        // 1. Filtro por Rango de Folios (Prioritario si se usa)
        // El folio_number tiene formato 'FOLIO-XXXXXX' (padded a 6 dígitos por owner).
        // Comparamos como string padded — la comparación lexicográfica equivale a la numérica.
        $filteringByFolio = false;

        if ($folioFrom !== null && $folioFrom !== '') {
            $query->where('folio_number', '>=', 'FOLIO-' . str_pad((int) $folioFrom, 6, '0', STR_PAD_LEFT));
            $filteringByFolio = true;
        }
        if ($folioTo !== null && $folioTo !== '') {
            $query->where('folio_number', '<=', 'FOLIO-' . str_pad((int) $folioTo, 6, '0', STR_PAD_LEFT));
            $filteringByFolio = true;
        }

        // 2. Filtro por Fechas
        // Si se está filtrando por folio, ignoramos fechas — el folio es prioritario
        // y los folios pueden ser de meses anteriores al rango por defecto.
        // Si NO se filtra por folio, aplicamos SIEMPRE el rango (con default = mes actual),
        // igual que IncomeExport. Sin esto, entrar sin parámetros traía todo el histórico
        // (miles de transactions con relaciones anidadas) y agotaba la memoria de PHP al render.
        if (!$filteringByFolio) {
            $query->whereBetween('payment_date', [$startDate, $endDate]);
        }

        // 3. Filtro por Socio (usa owner_id snapshot de la transacción)
        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }

        // 4. Filtro por Método de pago
        if ($paymentMethod && array_key_exists($paymentMethod, \App\Models\Transaction::PAYMENT_METHODS)) {
            $query->where('payment_method', $paymentMethod);
        }

        $transactions = $query->orderBy('payment_date', 'desc')->get();

        // Solo sumar transacciones activas en los totales
        $incomeByCurrency = $transactions->filter(fn($t) => $t->status === 'active')->groupBy(function ($tr) {
            return $tr->installments->first()?->paymentPlan?->currency ?? 'MXN';
        })->map(function ($group) {
            return $group->sum('amount_paid');
        });

        $owners = \App\Models\Owner::orderBy('name')->get();

        return view('reports.income', [
            'transactions' => $transactions,
            'incomeByCurrency' => $incomeByCurrency,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'owners' => $owners,
            'folio_search' => $request->input('folio_search'),
            'selectedOwner' => $request->input('owner_id'),
            'selectedPaymentMethod' => $paymentMethod,
        ]);
    }

    public function overdueInstallments(Request $request)
    {
        $query = \App\Models\Installment::where('status', 'vencida')
            ->whereHas('paymentPlan', fn($q) => $q->where('status', 'active'))
            ->with(['paymentPlan.lot.client', 'paymentPlan.lot', 'transactions'])
            ->when($request->owner_id, fn($q, $v) => $q->whereHas('paymentPlan.lot', fn($sq) => $sq->where('owner_id', $v)))
            ->when($request->block_number, fn($q, $v) => $q->whereHas('paymentPlan.lot', fn($sq) => $sq->where('block_number', 'like', "%$v%")))
            ->orderBy('due_date', 'asc');

        $overdueInstallments = $query->paginate(25)->withQueryString();
        $owners = Owner::orderBy('name')->get();
        $selectedOwner = $request->owner_id;
        $blockNumber = $request->block_number;

        return view('reports.overdue', compact('overdueInstallments', 'owners', 'selectedOwner', 'blockNumber'));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $ownerId = $request->input('owner_id');
        $folioFrom = $request->input('folio_from');
        $folioTo = $request->input('folio_to');
        $paymentMethod = $request->input('payment_method');

        // Admin exporta todo; no-admin solo descarga su propio corte:
        // el filtro por su user_id se fuerza server-side, no es modificable por URL.
        $userId = auth()->user()->isAdmin() ? null : auth()->id();

        return Excel::download(new IncomeExport($startDate, $endDate, $ownerId, $folioFrom, $folioTo, $paymentMethod, $userId), 'reporte_ingresos.xlsx');
    }

    public function exportOverdue(Request $request)
    {
        return Excel::download(
            new OverdueInstallmentsExport($request->owner_id, $request->block_number),
            'cuotas_vencidas.xlsx'
        );
    }

}