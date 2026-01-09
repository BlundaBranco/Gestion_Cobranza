<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\Api\ClientInstallmentController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\LotTransferController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\OwnerTransferController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\BulkInstallmentUpdateController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // --- PERFIL DE USUARIO ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- RECURSOS CRUD PRINCIPALES ---
    Route::resource('clients', ClientController::class);
    Route::resource('lots', LotController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('owners', OwnerController::class);

    // --- GESTIÓN DE CLIENTES ---
    // Exportar Estado de Cuenta (CORRECCIÓN: Faltaba esta ruta)
    Route::get('clients/{client}/export', [ClientController::class, 'export'])->name('clients.export');
    
    // Documentos
    Route::post('clients/{client}/documents', [ClientDocumentController::class, 'store'])->name('clients.documents.store');
    Route::delete('documents/{document}', [ClientDocumentController::class, 'destroy'])->name('documents.destroy');
    
    // API interna para obtener cuotas en el formulario de pago
    Route::get('/clients/{client}/pending-installments', [ClientInstallmentController::class, 'index'])->name('clients.pending-installments');

    // --- GESTIÓN DE LOTES ---
    // Transferencias
    Route::post('lots/{lot}/transfer', [LotTransferController::class, 'transfer'])->name('lots.transfer');
    Route::post('lots/{lot}/transfer-owner', [OwnerTransferController::class, 'transfer'])->name('lots.transfer-owner');

    // --- PLANES DE PAGO ---
    Route::post('lots/{lot}/payment-plans', [PaymentPlanController::class, 'store'])->name('lots.payment-plans.store');
    Route::delete('payment-plans/{plan}', [PaymentPlanController::class, 'destroy'])->name('payment-plans.destroy');
    
    // --- CUOTAS (INSTALLMENTS) ---
    // Agregar cuota individual
    Route::post('/payment-plans/{plan}/installments', [InstallmentController::class, 'store'])->name('payment-plans.installments.store');
    // Edición masiva
    Route::post('/payment-plans/{plan}/bulk-update-installments', [BulkInstallmentUpdateController::class, 'update'])->name('installments.bulk-update');
    // Edición individual (Monto y Fecha)
    Route::put('/installments/{installment}', [InstallmentController::class, 'update'])->name('installments.update');
    // Condonar interés
    Route::post('/installments/{installment}/condone-interest', [InstallmentController::class, 'condoneInterest'])->name('installments.condone');

    // --- TRANSACCIONES ---
    // PDF del Recibo
    Route::get('transactions/{transaction}/pdf', [TransactionController::class, 'showPdf'])->name('transactions.pdf');
    
    // Resource: Habilitamos index, create, store, update y destroy.
    // show y edit no se usan (se usa pdf y modales/inline).
    Route::resource('transactions', TransactionController::class)->except(['show', 'edit']);

    // --- REPORTES ---
    Route::get('reports/income', [ReportController::class, 'incomeReport'])->name('reports.income');
    Route::get('reports/overdue-installments', [ReportController::class, 'overdueInstallments'])->name('reports.overdue');
    Route::get('reports/income/export', [ReportController::class, 'export'])->name('reports.export');
   
    
    Route::put('/payment-plans/{plan}/update-currency', [PaymentPlanController::class, 'updateCurrency'])->name('payment-plans.updateCurrency');
    

    Route::put('/installments/{installment}/interest', [InstallmentController::class, 'updateInterest'])->name('installments.update-interest');
    Route::post('/installments/bulk-condone', [InstallmentController::class, 'bulkCondone'])->name('installments.bulk-condone');

});

require __DIR__.'/auth.php';