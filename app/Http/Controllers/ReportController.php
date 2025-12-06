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
        // Obtener todos los parámetros del request, con valores por defecto
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $ownerId = $request->input('owner_id');
        $folioSearch = $request->input('folio_search');

        // Iniciar la consulta con las relaciones necesarias
        $query = Transaction::with(['client', 'installments.paymentPlan.lot.owner']);

        // Aplicar filtros solo si los valores están presentes
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$startDate, $endDate]);
        }

        if ($ownerId) {
            $query->whereHas('installments.paymentPlan.lot', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            });
        }
        
        // Nuevo filtro por número de folio
        if ($folioSearch) {
            $query->where('folio_number', 'like', '%' . $folioSearch . '%');
        }

        // Ejecutar la consulta
        $transactions = $query->latest('payment_date')->get();
        $totalIncome = $transactions->sum('amount_paid');
        
        // Obtener los socios para el desplegable
        $owners = \App\Models\Owner::orderBy('name')->get();

        // Pasar todas las variables a la vista
        return view('reports.income', [
            'transactions' => $transactions,
            'totalIncome' => $totalIncome,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'owners' => $owners,
            // Pasar de vuelta los valores seleccionados para mantenerlos en el formulario
            'selectedOwner' => $ownerId, 
            'folioSearch' => $folioSearch,
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