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
        
        // Parámetros de Rango de Folio
        $folioFrom = $request->input('folio_from');
        $folioTo = $request->input('folio_to');

        $query = \App\Models\Transaction::withTrashed()->with(['client', 'installments.paymentPlan.lot.owner']);

        // LOGICA DE FILTRADO
        
        // 1. Filtro por Rango de Folios (Prioritario si se usa)
        // Se asume que el ID de la transacción corresponde al número de folio
        $filteringByFolio = false;
        
        if ($folioFrom) {
            $query->where('id', '>=', $folioFrom);
            $filteringByFolio = true;
        }
        if ($folioTo) {
            $query->where('id', '<=', $folioTo);
            $filteringByFolio = true;
        }

        // 2. Filtro por Fechas
        // Si NO se está filtrando por folio, O si el usuario explícitamente selecciona fechas, aplicamos fecha.
        // (En este caso, aplicamos fechas siempre por defecto a menos que el usuario las limpie, 
        // pero mantendremos la lógica de que coexistan).
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$startDate, $endDate]);
        }

        // 3. Filtro por Socio
        if ($ownerId) {
            $query->whereHas('installments.paymentPlan.lot', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            });
        }

$transactions = $query->orderBy('payment_date', 'desc')->get();

        // Solo sumar transacciones activas en los totales
        $incomeByCurrency = $transactions->filter(fn($t) => $t->status === 'active')->groupBy(function ($tr) {
            return $tr->installments->first()->paymentPlan->currency ?? 'MXN';
        })->map(function ($group) {
            return $group->sum('amount_paid');
        });
        
        $owners = \App\Models\Owner::orderBy('name')->get();

        return view('reports.income', [
            'transactions' => $transactions,
            // 'totalIncome' => $totalIncome, // <-- ESTA VARIABLE SE ELIMINA
            'incomeByCurrency' => $incomeByCurrency, // <-- SE ENVÍA ESTA NUEVA
            'startDate' => $startDate,
            'endDate' => $endDate,
            'owners' => $owners,
            'folio_search' => $request->input('folio_search'), // Asegúrate de pasar esto si usas el filtro
            'selectedOwner' => $request->input('owner_id'),
        ]);
    }

    public function overdueInstallments(Request $request)
    {
        $query = \App\Models\Installment::where('status', 'vencida')
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

        return Excel::download(new IncomeExport($startDate, $endDate, $ownerId, $folioFrom, $folioTo), 'reporte_ingresos.xlsx');
    }

    public function exportOverdue(Request $request)
    {
        return Excel::download(
            new OverdueInstallmentsExport($request->owner_id, $request->block_number),
            'cuotas_vencidas.xlsx'
        );
    }

}