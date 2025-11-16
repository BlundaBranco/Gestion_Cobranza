<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lot;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\Installment; // Importación añadida
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Estadísticas generales (sin cambios)
        $stats = [
            'totalClients' => Client::count(),
            'totalLots' => Lot::count(),
            'transactionsThisMonth' => Transaction::whereBetween('payment_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])->count(),
        ];

        $lotStatusSummary = Lot::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $systemSummary = [
            'lotes_disponibles' => $lotStatusSummary->get('disponible', 0),
            'lotes_vendidos' => $lotStatusSummary->get('vendido', 0),
            'lotes_liquidados' => $lotStatusSummary->get('liquidado', 0),
            'servicios_activos' => Service::count(),
        ];

        $recentTransactions = Transaction::with('client')->latest()->take(5)->get();

        // --- LÓGICA MODIFICADA PARA CUOTAS VENCIDAS ---

        // 1. Crear la consulta base para cuotas vencidas.
        $overdueQuery = Installment::where('status', 'vencida')
            ->with(['paymentPlan.lot.client', 'transactions']);

        // 2. Obtener el conteo total de cuotas vencidas.
        $overdueInstallmentsCount = $overdueQuery->count();

        // 3. Obtener solo las primeras 5 (o 10) para mostrar en el dashboard.
        $overdueInstallments = $overdueQuery->orderBy('due_date', 'asc')->take(5)->get();

        // --- FIN DE LA MODIFICACIÓN ---

        return view('dashboard', compact(
            'stats', 
            'recentTransactions', 
            'systemSummary', 
            'overdueInstallments', 
            'overdueInstallmentsCount' // Pasar el nuevo conteo a la vista
        ));
    }
}