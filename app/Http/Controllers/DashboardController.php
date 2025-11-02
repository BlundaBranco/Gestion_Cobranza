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
        $stats = [
            'totalClients' => Client::count(),
            'totalLots' => Lot::count(),
            'transactionsThisMonth' => Transaction::whereBetween('payment_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])->count(),
        ];

        // Usar DB::raw para agrupar por estado de forma eficiente
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

        // Nueva consulta para obtener cuotas vencidas
        $overdueInstallments = Installment::where('status', 'vencida')
            ->with(['paymentPlan.lot.client', 'transactions'])
            ->orderBy('due_date', 'asc')
            ->take(10) // Limitar resultados para rendimiento del dashboard
            ->get();

        return view('dashboard', compact('stats', 'recentTransactions', 'systemSummary', 'overdueInstallments'));
    }
}