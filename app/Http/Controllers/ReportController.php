<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Owner; // Importar el modelo Owner
use Illuminate\Http\Request;
use Illuminate\Support\Carbon; // Necesario para las fechas por defecto

class ReportController extends Controller
{
    public function incomeReport(Request $request)
    {
        // Establecer fechas por defecto si no se proporcionan
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $ownerId = $request->input('owner_id'); // Obtener el ID del socio del request

        // Iniciar la consulta de transacciones
        $query = Transaction::with(['client', 'installments.paymentPlan.lot']); // Precargar relaciones necesarias

        // Filtrar por rango de fechas
        $query->whereBetween('payment_date', [$startDate, $endDate]);

        // Filtrar por socio si se proporcionó un ID
        if ($ownerId) {
            $query->whereHas('installments.paymentPlan.lot', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            });
        }

        // Obtener las transacciones y calcular el total
        $transactions = $query->orderBy('payment_date', 'desc')->get();
        $totalIncome = $transactions->sum('amount_paid');
        
        // Obtener la lista de socios para el filtro
        $owners = Owner::orderBy('name')->get();

        // Pasar todas las variables a la vista
        return view('reports.income', compact('transactions', 'totalIncome', 'startDate', 'endDate', 'owners', 'ownerId'));
    }

    public function overdueInstallments()
    {
        $overdueInstallments = \App\Models\Installment::where('status', 'vencida')
            ->with(['paymentPlan.lot.client', 'transactions'])
            ->orderBy('due_date', 'asc')
            ->paginate(25); // Paginar para manejar grandes cantidades

        return view('reports.overdue', compact('overdueInstallments'));
    }
    
}