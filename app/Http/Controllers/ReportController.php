<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Owner; // Importar el modelo Owner
use Illuminate\Http\Request;
use Illuminate\Support\Carbon; // Necesario para las fechas por defecto
use App\Exports\IncomeExport;
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

        $query = \App\Models\Transaction::with(['client', 'installments.paymentPlan.lot.owner']);

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

        // --- CORRECCIÓN: Agrupar totales por moneda ---
        $incomeByCurrency = $transactions->groupBy(function ($tr) {
            // Obtenemos la moneda del plan de pago asociado a la primera cuota
            // Si no tiene, asumimos MXN
            return $tr->installments->first()->paymentPlan->currency ?? 'MXN';
        })->map(function ($group) {
            return $group->sum('amount_paid');
        });
        // ----------------------------------------------
        
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

    public function overdueInstallments()
    {
        $overdueInstallments = \App\Models\Installment::where('status', 'vencida')
            ->with(['paymentPlan.lot.client', 'transactions'])
            ->orderBy('due_date', 'asc')
            ->paginate(25); // Paginar para manejar grandes cantidades

        return view('reports.overdue', compact('overdueInstallments'));
    }
    
    public function export(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $ownerId = $request->input('owner_id');

        return Excel::download(new IncomeExport($startDate, $endDate, $ownerId), 'reporte_ingresos.xlsx');
    }

}